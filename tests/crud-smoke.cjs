const { chromium } = require(process.env.PLAYWRIGHT_PATH || 'playwright');
const { spawn, execFileSync } = require('node:child_process');
const { randomBytes } = require('node:crypto');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const php = process.env.PHP_BINARY || 'C:/xampp/php/php.exe';
const database = 'expenzo_crud_test_' + randomBytes(6).toString('hex');
const base = 'http://127.0.0.1:18765';
let server, browser, created = false;
let checks = 0;
const check = (condition, message) => { assert.ok(condition, message); checks++; };
async function main() {
  created = true;
  const fixtures = JSON.parse(execFileSync(php, ['tests/crud-database.php', 'create', database], {cwd: root, windowsHide: true, encoding: 'utf8'}));
  server = spawn(php, ['-S', '127.0.0.1:18765', '-t', 'public', 'tests/crud-router.php'], {
    cwd: root, windowsHide: true, env: {...process.env, DB_DATABASE: database, SESSION_NAME: 'expenzo_crud_test', APP_DEBUG: '1'},
    stdio: ['ignore', 'pipe', 'pipe']
  });
  let serverLog = '';
  server.stderr.on('data', d => serverLog += d.toString());
  server.stdout.on('data', d => serverLog += d.toString());
  for (let i = 0; i < 50; i++) {
    try { if ((await fetch(base + '/login')).ok) break; } catch {}
    await new Promise(r => setTimeout(r, 100));
  }
  browser = await chromium.launch({channel: 'chrome', headless: true});
  async function login(email) {
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto(base + '/login');
    await page.locator('[name=email]').fill(email);
    await page.locator('[name=password]').fill('CrudTest123!');
    await Promise.all([page.waitForURL('**/dashboard'), page.getByRole('button', {name:'Sign in', exact:true}).click()]);
    return {context, page};
  }
  const user = await login('user@expenzo.com');
  const admin = await login('ops.admin@expenzo.com');
  const superAdmin = await login('admin@expenzo.com');

  async function pickIcon(page, query, iconName, keyboard = false) {
    const trigger = page.locator('.icon-picker-trigger');
    await trigger.click();
    const search = page.getByRole('combobox', {name:'Search icons'});
    await search.fill(query);
    const option = page.locator('#icon-option-' + iconName);
    check(await option.locator('svg').count() === 1, 'icon option has a visual preview');
    if (keyboard) await search.press('Enter');
    else await option.click();
    check(await page.locator('select[name=icon]').inputValue() === iconName, 'selected icon updates submitted value');
    check(await trigger.locator('svg').count() === 1, 'selected icon has a visual preview');
    check(await trigger.getAttribute('aria-expanded') === 'false', 'picker closes after selection');
  }
  async function token(page, route) {
    await page.goto(base + route);
    return await page.locator('input[name=_csrf]').first().inputValue();
  }
  async function post(page, route, form, expected = 302) {
    const response = await page.request.post(base + route, {form, maxRedirects: 0});
    check(response.status() === expected, route + ' expected ' + expected + ' got ' + response.status());
    return response;
  }
  async function status(page, route, expected) {
    const response = await page.goto(base + route);
    check(response.status() === expected, route + ' status');
  }
  async function save(page, expected) {
    await Promise.all([page.waitForURL(base + expected), page.locator('button[type=submit]').click()]);
    check(await page.locator('.alert-success').count() === 1, expected + ' success flash');
  }
  for (const [actor, modules] of [[user, ['categories','accounts','budgets']], [admin, ['categories','accounts','budgets','users']]]) {
    for (const module of modules) {
      await status(actor.page, '/' + module, 200);
      check(await actor.page.locator('main input[name=name]').count() === 0, module + ' has no embedded create form');
    }
  }
  await status(user.page, '/categories/create', 200);
  await status(user.page, '/categories/1/edit', 403);
  await status(user.page, '/users/create', 403);
  await status(admin.page, '/accounts/create', 403);
  await status(admin.page, '/budgets/create', 403);
  await status(admin.page, '/users/1/edit', 403);
  for (const [module, id] of [['accounts', fixtures.foreignAccount], ['budgets', fixtures.foreignBudget]]) {
    await status(user.page, '/' + module + '/' + id + '/edit', 404);
    const csrf = await token(user.page, '/' + module + '/create');
    await post(user.page, '/' + module + '/' + id + '/update', {_csrf: csrf}, 404);
    await post(user.page, '/' + module + '/' + id + '/delete', {_csrf: csrf}, 404);
  }
  let csrf = await token(user.page, '/accounts/create');
  await post(user.page, '/accounts', {_csrf: 'invalid'}, 419);
  const invalid = await post(user.page, '/accounts', {_csrf: csrf, name: 'Retained account', type: 'invalid', status: 'active', opening_balance: 'x'}, 422);
  check((await invalid.text()).includes('value="Retained account"'), 'invalid submission retains values');
  await user.page.locator('[name=name]').fill('CRUD account');
  await user.page.locator('[name=opening_balance]').fill('120.50');
  await save(user.page, '/accounts');
  const accountRow = user.page.locator('tr').filter({hasText: 'CRUD account'});
  const accountEdit = await accountRow.getByRole('link', {name: /Edit/}).getAttribute('href');
  await user.page.goto(base + accountEdit);
  check(await user.page.locator('[name=opening_balance]').count() === 0, 'opening balance cannot be changed on edit');
  await user.page.locator('[name=name]').fill('CRUD account updated');
  await save(user.page, '/accounts');
  check((await user.page.locator('tr').filter({hasText: 'CRUD account updated'}).innerText()).includes('120.50'), 'account balance preserved');
  await user.page.goto(base + accountEdit);
  await save(user.page, '/accounts');
  await user.page.goto(base + '/budgets/create');
  await user.page.locator('[name=budget_amount]').fill('250');
  await save(user.page, '/budgets');
  const budgetRow = user.page.locator('tr').filter({hasText: '250.00'});
  const budgetEdit = await budgetRow.getByRole('link', {name: /Edit/}).getAttribute('href');
  await user.page.goto(base + budgetEdit);
  csrf = await user.page.locator('[name=_csrf]').inputValue();
  await post(user.page, budgetEdit.replace('/edit','/update'), {_csrf: csrf, start_date: '2026-02-30', end_date: '2026-01-01', budget_amount: '-1', warning_threshold: '101', status: 'active'}, 422);
  await user.page.locator('[name=budget_amount]').fill('300');
  await user.page.locator('[name=status]').selectOption('inactive');
  await save(user.page, '/budgets');
  check((await user.page.locator('tr').filter({has: user.page.locator('a[href="' + budgetEdit + '"]')}).innerText()).includes('Inactive'), 'budget edit persisted');
  await user.page.goto(base + '/categories/1/appearance');
  await pickIcon(user.page, 'coffee', 'coffee');
  await user.page.locator('[name=badge]').fill('Mine');
  await user.page.locator('[name=color]').fill('#123456');
  await save(user.page, '/categories');
  check((await user.page.locator('tbody').innerText()).includes('Mine'), 'personal appearance persisted');
  await admin.page.goto(base + '/categories');
  check(!(await admin.page.locator('tbody').innerText()).includes('Mine'), 'personal appearance is isolated');
  await admin.page.goto(base + '/categories/create');
  await pickIcon(admin.page, 'heart', 'heart');
  await admin.page.locator('[name=name]').fill('CRUD category');
  await save(admin.page, '/categories');
  const categoryEdit = await admin.page.locator('tr').filter({hasText: 'CRUD category'}).getByRole('link', {name: /Edit/}).getAttribute('href');
  await admin.page.goto(base + categoryEdit);
  await admin.page.locator('[name=name]').fill('CRUD category updated');
  await save(admin.page, '/categories');
  csrf = await token(admin.page, '/users/create');
  await post(admin.page, '/users', {_csrf: csrf, name:'Blocked', email:'blocked@example.test', password:'CrudTest123!', password_confirmation:'CrudTest123!', role_id:'1', status:'active'}, 422);
  await admin.page.locator('[name=name]').fill('CRUD user');
  await admin.page.locator('[name=email]').fill('crud@example.test');
  await admin.page.locator('[name=password]').fill('CrudTest123!');
  await admin.page.locator('[name=password_confirmation]').fill('CrudTest123!');
  await admin.page.locator('[name=role_id]').selectOption('3');
  await save(admin.page, '/users');
  const userEdit = await admin.page.locator('tr').filter({hasText: 'crud@example.test'}).getByRole('link', {name:/Edit/}).getAttribute('href');
  await admin.page.goto(base + userEdit);
  check(await admin.page.locator('[name=password]').count() === 0, 'edit has no password field');
  await admin.page.locator('[name=name]').fill('CRUD user updated');
  await admin.page.locator('[name=status]').selectOption('inactive');
  await save(admin.page, '/users');
  await admin.page.locator('[name=q]').fill('crud@example.test');
  await admin.page.getByRole('button', {name:'Search', exact:true}).click();
  check(await admin.page.locator('tbody tr').count() === 1, 'users search works');
  check((await admin.page.locator('tbody').innerText()).includes('Inactive'), 'user status update persisted');

  await admin.page.goto(base + categoryEdit);
  await pickIcon(admin.page, 'shopping bag', 'shopping-bag', true);
  await save(admin.page, '/categories');
  await admin.page.goto(base + categoryEdit);
  check(await admin.page.locator('select[name=icon]').inputValue() === 'shopping-bag', 'category icon selection persists');
  await admin.page.locator('.icon-picker-trigger').click();
  await admin.page.getByRole('combobox', {name:'Search icons'}).fill('no-such-icon-zzzz');
  check(await admin.page.locator('.icon-picker-options [role=option]').count() === 0, 'unknown search has no results');
  check(await admin.page.locator('.icon-picker-empty').isVisible(), 'empty search shows guidance');
  await admin.page.getByRole('combobox', {name:'Search icons'}).press('Escape');
  check(await admin.page.locator('.icon-picker-trigger').evaluate(el => el === document.activeElement), 'Escape restores focus');
  for (const width of [320, 768, 1440]) {
    await admin.page.setViewportSize({width, height:900});
    await admin.page.locator('.icon-picker-trigger').click();
    await admin.page.getByRole('combobox', {name:'Search icons'}).fill('coffee');
    const bounds = await admin.page.locator('.icon-picker-panel').boundingBox();
    check(bounds.x >= 0 && bounds.x + bounds.width <= width + 1, 'open picker fits viewport at ' + width);
    await admin.page.screenshot({path:path.join(root, 'storage/cache/icon-picker-' + width + '.png'), fullPage:true});
    await admin.page.getByRole('combobox', {name:'Search icons'}).press('Escape');
  }
  await require('./scope-role-checks.cjs')({base,user,admin,superAdmin,fixtures,login,token,post,status,save,check});
  const screens = [
    [user.page, '/accounts'], [user.page, '/accounts/create'], [user.page, accountEdit],
    [user.page, '/budgets'], [user.page, '/budgets/create'], [user.page, budgetEdit],
    [user.page, '/categories'], [user.page, '/categories/1/appearance'],
    [admin.page, '/categories/create'], [admin.page, categoryEdit],
    [admin.page, '/users'], [admin.page, '/users/create'], [admin.page, userEdit],
    [superAdmin.page, '/settings'], [user.page, '/expenses'], [user.page, '/expenses/create']
  ];
  for (const width of [320, 375, 768, 1440]) {
    for (const [page, route] of screens) {
      await page.setViewportSize({width, height:900});
      await status(page, route, 200);
      await page.waitForLoadState('networkidle');
      check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), route + ' no page overflow at ' + width);
      const module = route.split('/')[1];
      check(await page.locator('.sidebar-item.active .sidebar-link').getAttribute('href') === '/' + module, route + ' active sidebar');
      if (await page.locator('.crud-form').count()) {
        const fields = page.locator('.crud-form input:not([type=hidden]), .crud-form select, .crud-form textarea');
        for (const box of await fields.evaluateAll(nodes => nodes.map(n => {const r=n.getBoundingClientRect(); return {x:r.x,right:r.right};}))) {
          check(box.x >= 0 && box.right <= width + 1, route + ' field fits viewport');
        }
      }
    }
    await user.page.goto(base + '/accounts');
    await user.page.screenshot({path: path.join(root, 'storage/cache/crud-accounts-' + width + '.png'), fullPage: true});
  }
  for (const [page, edit] of [[user.page, accountEdit], [user.page, budgetEdit], [admin.page, categoryEdit]]) {
    await page.goto(base + edit);
    const cancel = await page.getByRole('link', {name:'Cancel', exact:true}).getAttribute('href');
    check(cancel === '/' + edit.split('/')[1], 'cancel returns to list');
    const csrf = await page.locator('[name=_csrf]').inputValue();
    await post(page, edit.replace('/edit','/delete'), {_csrf:csrf});
    await status(page, edit, 404);
  }
  csrf = await token(user.page, '/accounts/create');
  await post(user.page, '/accounts/1/delete', {_csrf:csrf});
  await user.page.goto(base + '/accounts');
  check((await user.page.locator('.alert-danger').innerText()).includes('deactivated'), 'used account delete gives friendly error');
  await status(user.page, '/accounts/1/delete', 404);
  check(!/PHP (Warning|Notice|Fatal|Parse)/.test(serverLog), 'no PHP runtime warnings');
  console.log('PASS: ' + checks + ' CRUD, permissions, ownership, validation, and responsive checks.');
}
main().catch(e => {console.error(e); process.exitCode=1;}).finally(async () => {
  if (browser) await browser.close();
  if (server) server.kill();
  if (created) {
    try { console.log(execFileSync(php, ['tests/crud-database.php','drop',database], {cwd:root, windowsHide:true, encoding:'utf8'}).trim()); }
    catch (e) {console.error('Could not clean up isolated test database ' + database); process.exitCode=1;}
  }
});