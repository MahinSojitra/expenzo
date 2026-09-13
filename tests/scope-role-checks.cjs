module.exports = async function ({base, user, admin, superAdmin, fixtures, login, token, post, status, save, check}) {
  const other = await login('other@example.test');
  const u = user.page, a = admin.page, s = superAdmin.page, o = other.page;
  const payload = (name, extras = {}) => ({name, description:'Scope test', icon:'tag', color:'#3b7ddd', status:'active', ...extras});
  const findEdit = async (page, name, module) => {
    await page.goto(base + '/' + module);
    return await page.locator('tr').filter({has:page.getByText(name, {exact:true})}).getByRole('link', {name:/Edit/}).getAttribute('href');
  };
  const idFrom = route => route.split('/')[2];
  let csrf = await token(u, '/categories/create');
  await post(u, '/categories', {_csrf:csrf, ...payload('Private A'), owner_id:fixtures.otherUser, scope:'global'});
  const personalEdit = await findEdit(u, 'Private A', 'categories');
  const personalId = idFrom(personalEdit);
  check((await u.locator('tr').filter({hasText:'Private A'}).innerText()).includes('Personal'), 'normal user creates personal category despite forged scope');
  await o.goto(base + '/categories');
  check(!(await o.locator('tbody').innerText()).includes('Private A'), 'other user cannot see personal category');
  for (const route of [personalEdit, '/categories/' + personalId + '/appearance']) await status(o, route, 404);
  csrf = await token(o, '/categories/create');
  await post(o, personalEdit.replace('/edit','/update'), {_csrf:csrf, ...payload('Stolen')}, 404);
  await post(o, personalEdit.replace('/edit','/delete'), {_csrf:csrf}, 404);
  await post(o, '/categories/' + personalId + '/appearance', {_csrf:csrf, badge:'Stolen', icon:'heart', color:'#112233'}, 404);
  await post(o, '/categories', {_csrf:csrf, ...payload('Private A')});
  check(!!(await findEdit(o, 'Private A', 'categories')), 'different owners can reuse category names');
  csrf = await token(u, personalEdit);
  await post(u, personalEdit.replace('/edit','/update'), {_csrf:csrf, ...payload('Private A updated'), owner_id:'', scope:'global'});
  await u.goto(base + '/categories');
  check((await u.locator('tr').filter({hasText:'Private A updated'}).innerText()).includes('Personal'), 'scope remains immutable on edit');
  await a.goto(base + '/categories?scope=personal&owner_id=3&status=active');
  check((await a.locator('tbody').innerText()).includes('Private A updated'), 'admin filters personal categories by owner and status');
  check((await a.locator('tbody').innerText()).includes('Demo User'), 'admin sees personal category owner');
  check(!(await a.locator('tbody').innerText()).includes('Other User'), 'owner filter excludes others');
  await status(a, personalEdit, 200);
  csrf = await token(a, '/categories/create');
  await post(a, '/categories', {_csrf:csrf, ...payload('Shared global')});
  const globalEdit = await findEdit(a, 'Shared global', 'categories');
  const globalId = idFrom(globalEdit);
  for (const page of [u,o]) {
    await page.goto(base + '/categories');
    check((await page.locator('tbody').innerText()).includes('Shared global'), 'new global category appears for every user');
    await status(page, globalEdit, 403);
  }
  await u.goto(base + '/expenses/create');
  check(await u.locator('select[name=category_id] option[value="' + personalId + '"]').count() === 1, 'expense picker contains own personal category');
  check(await u.locator('select[name=category_id] option[value="' + globalId + '"]').count() === 1, 'expense picker contains new global category');
  check(await u.locator('select[name=category_id] option[value="' + fixtures.foreignCategory + '"]').count() === 0, 'expense picker excludes foreign category');
  csrf = await u.locator('[name=_csrf]').inputValue();
  const expense = {amount:'25', expense_date:'2026-09-13', account_id:'1', description:'Scoped expense', status:'posted'};
  await post(u, '/expenses', {_csrf:csrf, ...expense, category_id:fixtures.foreignCategory}, 422);
  await post(u, '/expenses', {_csrf:csrf, ...expense, category_id:personalId});
  await u.goto(base + '/expenses');
  const expenseEdit = await u.locator('tr').filter({hasText:'Scoped expense'}).getByRole('link',{name:'Edit',exact:true}).getAttribute('href');
  csrf = await token(u, expenseEdit);
  await post(u, expenseEdit.replace('/edit',''), {_csrf:csrf, ...expense, category_id:fixtures.foreignCategory}, 422);
  csrf = await token(u, '/budgets/create');
  await post(u, '/budgets', {_csrf:csrf, category_id:fixtures.foreignCategory, start_date:'2026-09-01',end_date:'2026-09-30',budget_amount:200,warning_threshold:80,status:'active'}, 422);
  await u.goto(base + '/reports');
  check(await u.locator('[name=category_id] option[value="' + fixtures.foreignCategory + '"]').count() === 0, 'report picker excludes foreign categories');
  csrf = await token(u, personalEdit);
  await post(u, personalEdit.replace('/edit','/update'), {_csrf:csrf,...payload('Private A updated',{status:'inactive'})});
  await u.goto(base + '/expenses/create');
  check(await u.locator('[name=category_id] option[value="' + personalId + '"]').count() === 0, 'inactive category unavailable for new expenses');
  await post(u, '/expenses', {_csrf:csrf,...expense,category_id:personalId}, 422);
  await post(u, personalEdit.replace('/edit','/delete'), {_csrf:csrf});
  await u.goto(base + '/categories');
  check(await u.locator('.alert-danger').count() === 1, 'used personal category cannot be deleted');
  await post(u, expenseEdit.replace('/edit','/delete'), {_csrf:csrf});
  await post(u, personalEdit.replace('/edit','/delete'), {_csrf:csrf});
  await status(u, personalEdit, 404);

  // Role CRUD and live permission enforcement.
  await status(u, '/roles', 403);
  await status(a, '/roles/create', 200);
  check(await a.locator('main input[name=name]').count() === 1, 'separate role create form');
  await a.locator('[name=name]').fill('Expense Viewer');
  await a.locator('[name=description]').fill('Read-only personal expense reports');
  for (const permission of ['dashboard.view','expenses.view','reports.view']) {
    await a.locator('#permission-' + fixtures.permissions[permission]).check();
  }
  await save(a, '/roles');
  const viewerEdit = await findEdit(a, 'Expense Viewer', 'roles');
  const viewerId = idFrom(viewerEdit);
  await a.goto(base + '/users/create');
  check(await a.locator('[name=role_id] option[value="' + viewerId + '"]').count() === 1, 'new role immediately available to assign');
  await a.locator('[name=name]').fill('Viewer User');
  await a.locator('[name=email]').fill('viewer@example.test');
  await a.locator('[name=password]').fill('CrudTest123!');
  await a.locator('[name=password_confirmation]').fill('CrudTest123!');
  await a.locator('[name=role_id]').selectOption(viewerId);
  await save(a, '/users');
  const viewerUserEdit = await findEdit(a, 'Viewer User', 'users');
  const viewer = await login('viewer@example.test');
  await status(viewer.page, '/expenses', 200);
  check(await viewer.page.locator('.sidebar-link').count() === 3, 'viewer sidebar has only selected modules');
  check(await viewer.page.locator('a[href="/expenses/create"]').count() === 0, 'viewer cannot see create action');
  for (const route of ['/expenses/create','/expenses/1/edit','/categories','/accounts','/budgets','/users','/roles','/settings','/reports/csv']) await status(viewer.page, route, 403);
  await viewer.page.goto(base + '/reports');
  check(await viewer.page.getByRole('link',{name:'Export CSV'}).count() === 0, 'export button respects permission');
  await a.goto(base + '/roles');
  const viewerRow = a.locator('tr').filter({hasText:'Expense Viewer'});
  check((await viewerRow.locator('td').nth(3).innerText()) === '1', 'role user count reflects assignment');
  csrf = await token(a, viewerEdit);
  await post(a, viewerEdit.replace('/edit','/delete'), {_csrf:csrf});
  await a.goto(base + '/roles');
  check((await a.locator('.alert-danger').innerText()).includes('Reassign'), 'assigned role deletion blocked');

  // Update an existing role and verify an already signed-in user sees revocation.
  await a.goto(base + viewerEdit);
  await a.locator('#permission-' + fixtures.permissions['reports.view']).uncheck();
  await save(a, '/roles');
  await status(viewer.page, '/reports', 403);
  check(await viewer.page.locator('.sidebar-link[href="/reports"]').count() === 0, 'revoked permission removed from sidebar immediately');
  await a.goto(base + viewerEdit);
  await a.locator('[name=status]').selectOption('inactive');
  await save(a, '/roles');
  await status(viewer.page, '/expenses', 403);
  await a.goto(base + '/users/create');
  check(await a.locator('[name=role_id] option[value="' + viewerId + '"]').count() === 0, 'inactive roles excluded from assignments');
  csrf = await token(a, viewerUserEdit);
  await post(a, viewerUserEdit.replace('/edit','/update'), {_csrf:csrf,name:'Viewer User',email:'viewer@example.test',status:'active',role_id:'3'});
  await status(viewer.page, '/categories', 200);
  csrf = await token(a, viewerEdit);
  await post(a, viewerEdit.replace('/edit','/delete'), {_csrf:csrf});
  await status(a, viewerEdit, 404);

  // Permission selection tools, CSRF, protected roles and privilege escalation.
  await a.goto(base + '/roles/create');
  const group = a.locator('[data-permission-group]').filter({has:a.locator('legend').getByText('Expenses',{exact:true})});
  await group.getByRole('button',{name:'Select all',exact:true}).click();
  check(await group.locator('input:checked').count() === 4, 'module select-all works');
  await group.getByRole('button',{name:'Clear all',exact:true}).click();
  check(await group.locator('input:checked').count() === 0, 'module clear-all works');
  const rawPost = async (page, route, values, expected) => {
    const data = new URLSearchParams();
    for (const [key,value] of Object.entries(values)) {
      for (const entry of Array.isArray(value) ? value : [value]) data.append(key, entry);
    }
    const response = await page.request.post(base + route,{data:data.toString(),headers:{'Content-Type':'application/x-www-form-urlencoded'},maxRedirects:0});
    check(response.status() === expected, route + ' protects role permissions');
    return response;
  };
  csrf = await a.locator('[name=_csrf]').inputValue();
  await rawPost(a,'/roles',{_csrf:csrf,name:'Escalation',status:'active','permissions[]':[fixtures.permissions['settings.edit']]},422);
  await post(a,'/roles',{_csrf:'bad',name:'Invalid CSRF',status:'active'},419);
  for (const page of [a,s]) {
    await status(page,'/roles/1/edit',403);
    const tokenValue = await token(page,'/roles/create');
    await post(page,'/roles/1/update',{_csrf:tokenValue,name:'Renamed',status:'inactive'},403);
    await post(page,'/roles/1/delete',{_csrf:tokenValue},403);
  }
  await status(a,'/roles/2/edit',403);
  csrf = await token(s,'/users/1/edit');
  await post(s,'/users/1/update',{_csrf:csrf,name:'System Administrator',email:'admin@expenzo.com',status:'inactive',role_id:'1'},422);
  await post(s,'/users/1/update',{_csrf:csrf,name:'System Administrator',email:'admin@expenzo.com',status:'active',role_id:'3'},422);
  await status(s,'/roles',200);
  csrf = await token(a,'/users/2/edit');
  await post(a,'/users/2/update',{_csrf:csrf,name:'Operations Admin',email:'ops.admin@expenzo.com',status:'active',role_id:'1'},422);
  await post(a,'/settings',{_csrf:csrf,app_name:'Not allowed'},403);

  // A custom role cannot exploit role editing or assignment to gain stronger access.
  await s.goto(base + '/roles/create');
  await s.locator('[name=name]').fill('Limited Role Manager');
  for (const permission of ['dashboard.view','roles.view','roles.create','roles.edit','roles.delete','permissions.manage','users.view','users.edit','users.assign_role']) await s.locator('#permission-' + fixtures.permissions[permission]).check();
  await save(s,'/roles');
  const limitedEdit = await findEdit(s,'Limited Role Manager','roles');
  const limitedId = idFrom(limitedEdit);
  csrf = await token(s,viewerUserEdit);
  await post(s,viewerUserEdit.replace('/edit','/update'),{_csrf:csrf,name:'Viewer User',email:'viewer@example.test',status:'active',role_id:limitedId});
  await status(viewer.page,'/roles/create',200);
  csrf = await viewer.page.locator('[name=_csrf]').inputValue();
  await rawPost(viewer.page,'/roles',{_csrf:csrf,name:'Unauthorized stronger role',status:'active','permissions[]':[fixtures.permissions['expenses.create']]},422);
  await status(viewer.page,'/users/1/edit',403);
  await status(viewer.page,'/roles/2/edit',403);

  for (const width of [320,375,768,1440]) {
    for (const route of ['/roles','/roles/create',limitedEdit,'/categories?scope=personal']) {
      await s.setViewportSize({width,height:900});
      await status(s,route,200);
      check(await s.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), route + ' fits ' + width);
    }
    await s.goto(base + '/roles/create');
    await s.screenshot({path:require('node:path').join(__dirname,'../storage/cache/roles-' + width + '.png'),fullPage:true});
  }

  // Revoking permissions.manage removes permission-authoring access, even with roles.create/edit.
  await s.goto(base + '/roles/2/edit');
  const adminPermissionIds = await s.locator('input[name="permissions[]"]:checked').evaluateAll(nodes => nodes.map(n => n.value));
  await s.locator('#permission-' + fixtures.permissions['permissions.manage']).uncheck();
  await save(s,'/roles');
  await a.goto(base + '/roles');
  check(await a.getByRole('link',{name:'+ Add Role',exact:true}).count() === 0, 'Add Role hidden without permissions.manage');
  check(await a.locator('a[href="' + limitedEdit + '"]').count() === 0, 'Edit Role hidden without permissions.manage');
  await status(a,'/roles/create',403);
  await status(a,limitedEdit,403);
  csrf = await token(a,'/users/create');
  await post(a,'/roles',{_csrf:csrf,name:'Denied role',status:'active'},403);
  await post(a,limitedEdit.replace('/edit','/update'),{_csrf:csrf,name:'Denied edit',status:'active'},403);
  await s.goto(base + '/roles/2/edit');
  await s.locator('#permission-' + fixtures.permissions['permissions.manage']).check();
  await save(s,'/roles');
  await a.goto(base + '/roles');
  check(await a.getByRole('link',{name:'+ Add Role',exact:true}).count() === 1, 'Restoring permission restores button without re-login');
  await other.context.close();
  await viewer.context.close();
};