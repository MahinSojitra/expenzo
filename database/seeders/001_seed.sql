USE expenzo;

INSERT INTO roles(name,description,is_system,system_key) VALUES
('Super Admin','Protected full system access.',1,'super_admin'),
('User','Personal expenses, accounts, budgets and categories.',1,'user');

INSERT INTO permissions(name) VALUES
('dashboard.view'),
('expenses.view'),('expenses.create'),('expenses.edit'),('expenses.delete'),
('categories.view'),('categories.create'),('categories.edit'),('categories.delete'),('categories.customize'),
('categories.view_all'),('categories.manage_global'),('categories.manage_all'),('finance.view_all'),
('categories.badge.view'),('categories.badge.edit'),
('accounts.view'),('accounts.create'),('accounts.edit'),('accounts.delete'),
('budgets.view'),('budgets.create'),('budgets.edit'),('budgets.delete'),
('reports.view'),('reports.export'),
('users.view'),('users.create'),('users.edit'),('users.delete'),('users.assign_role'),
('roles.view'),('roles.create'),('roles.edit'),('roles.delete'),
('permissions.view'),('permissions.manage'),
('settings.view'),('settings.edit'),
('audit.view');

INSERT INTO users(name,email,password_hash,status,created_at,updated_at) VALUES
('System Administrator','admin@expenzo.com','$2y$10$ua.R9og84rbeQeYWbHi2.OFoC7yB43Owla1JljOJkSj0KDnIaGClu','active',NOW(),NOW()),
('Demo User','user@expenzo.com','$2y$10$ua.R9og84rbeQeYWbHi2.OFoC7yB43Owla1JljOJkSj0KDnIaGClu','active',NOW(),NOW());

INSERT INTO user_roles(user_id,role_id)
SELECT u.id,r.id FROM users u JOIN roles r ON r.name='Super Admin' WHERE u.email='admin@expenzo.com'
UNION ALL SELECT u.id,r.id FROM users u JOIN roles r ON r.name='User' WHERE u.email='user@expenzo.com';

INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p WHERE r.name='Super Admin';

INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.name IN (
'dashboard.view','expenses.view','expenses.create','expenses.edit','expenses.delete',
'categories.view','categories.create','categories.edit','categories.delete','categories.customize','categories.badge.view','categories.badge.edit',
'accounts.view','accounts.create','accounts.edit','accounts.delete',
'budgets.view','budgets.create','budgets.edit','budgets.delete','reports.view','reports.export'
) WHERE r.name='User';

INSERT INTO categories(name,description,icon,color,status,created_at,updated_at) VALUES
('Food','Meals, snacks and groceries','coffee','#3b7ddd','active',NOW(),NOW()),
('Transport','Travel, commute and fuel','truck','#e83e8c','active',NOW(),NOW()),
('Utilities','Electricity, water, internet and phone','zap','#fd7e14','active',NOW(),NOW()),
('Entertainment','Movies, games and leisure','film','#6f42c1','active',NOW(),NOW()),
('Health','Medical and wellness','heart','#20c997','active',NOW(),NOW()),
('Shopping','General shopping','shopping-bag','#17a2b8','active',NOW(),NOW()),
('Bills','Recurring bills and subscriptions','file-text','#dc3545','active',NOW(),NOW()),
('Education','Books, courses and learning','book-open','#198754','active',NOW(),NOW()),
('Personal Care','Grooming and personal care','smile','#0dcaf0','active',NOW(),NOW()),
('Other','Uncategorized expenses','tag','#6c757d','active',NOW(),NOW());

INSERT INTO accounts(user_id,name,type,opening_balance,current_balance,description,status,created_at,updated_at)
SELECT u.id,'Cash','Cash',10000.00,9500.00,'Physical cash','active',NOW(),NOW() FROM users u WHERE u.email='user@expenzo.com'
UNION ALL SELECT u.id,'Bank Account','Bank',50000.00,48500.00,'Primary bank account','active',NOW(),NOW() FROM users u WHERE u.email='user@expenzo.com'
UNION ALL SELECT u.id,'UPI','UPI',8000.00,7300.00,'UPI wallet','active',NOW(),NOW() FROM users u WHERE u.email='user@expenzo.com'
UNION ALL SELECT u.id,'Credit Card','Card',25000.00,25000.00,'Expense payment card limit tracker','active',NOW(),NOW() FROM users u WHERE u.email='user@expenzo.com';

INSERT INTO user_category_settings(user_id,category_id,badge,icon,color,created_at,updated_at)
SELECT u.id,c.id,CASE c.name WHEN 'Food' THEN '🍔' WHEN 'Transport' THEN '🚗' WHEN 'Shopping' THEN '🛍️' WHEN 'Utilities' THEN '💡' ELSE NULL END,c.icon,c.color,NOW(),NOW()
FROM users u CROSS JOIN categories c WHERE u.email='user@expenzo.com';

INSERT INTO expenses(user_id,amount,expense_date,category_id,account_id,description,notes,status,created_at,updated_at)
SELECT u.id,500.00,CURDATE(),c.id,a.id,'Dinner','Seeded demo expense','posted',NOW(),NOW() FROM users u JOIN categories c ON c.name='Food' JOIN accounts a ON a.user_id=u.id AND a.name='Cash' WHERE u.email='user@expenzo.com'
UNION ALL SELECT u.id,1500.00,DATE_SUB(CURDATE(),INTERVAL 2 DAY),c.id,a.id,'Fuel refill','Seeded demo expense','posted',NOW(),NOW() FROM users u JOIN categories c ON c.name='Transport' JOIN accounts a ON a.user_id=u.id AND a.name='Bank Account' WHERE u.email='user@expenzo.com'
UNION ALL SELECT u.id,700.00,DATE_SUB(CURDATE(),INTERVAL 5 DAY),c.id,a.id,'Mobile bill','Seeded demo expense','posted',NOW(),NOW() FROM users u JOIN categories c ON c.name='Utilities' JOIN accounts a ON a.user_id=u.id AND a.name='UPI' WHERE u.email='user@expenzo.com';

INSERT INTO budgets(user_id,category_id,start_date,end_date,budget_amount,warning_threshold,status,created_at,updated_at)
SELECT u.id,NULL,DATE_FORMAT(CURDATE(),'%Y-%m-01'),LAST_DAY(CURDATE()),20000.00,80.00,'active',NOW(),NOW() FROM users u WHERE u.email='user@expenzo.com'
UNION ALL SELECT u.id,c.id,DATE_FORMAT(CURDATE(),'%Y-%m-01'),LAST_DAY(CURDATE()),7000.00,80.00,'active',NOW(),NOW() FROM users u JOIN categories c ON c.name='Food' WHERE u.email='user@expenzo.com';

INSERT INTO settings(setting_key,setting_value,updated_at) VALUES
('app_name','Expenzo',NOW()),('currency','INR',NOW()),('date_format','Y-m-d',NOW()),('timezone','Asia/Kolkata',NOW()),('pagination_size','10',NOW());
