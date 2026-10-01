const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const names = ['type','transaction_date','account_id','destination_account_id','amount','direction','description'];
const classes = () => ({toggle() {}});
const option = (value) => ({value, textContent:value, disabled:false});
const controls = {};
for (const name of names) {
const listeners = {};
controls[name] = {value:'',required:true,disabled:false,validity:{},classList:classes(),tagName:'INPUT',
closest:()=>({hidden:false,append(){}}),setAttribute(){},setCustomValidity(message){this.error=message;},
addEventListener(event,fn){listeners[event]=fn;},dispatchEvent(){},listeners};
}
for (const name of ['type','account_id','destination_account_id','direction']) controls[name].tagName='SELECT';
for (const name of ['account_id','destination_account_id']) {
controls[name].options=['','1','2','3'].map(option);
Object.defineProperty(controls[name],'selectedOptions',{get(){return this.options.filter(o=>o.value===this.value);}});
}
controls.type.value='income';
controls.direction.value='increase';
controls.transaction_date.value='2026-10-01';
controls.transaction_date.max='2026-10-01';
controls.description.value='Test';
const form={elements:controls,addEventListener(){},noValidate:false};
const
context={document:{readyState:'complete',getElementById:id=>id==='transaction-validation-data'?{textContent:'{"1":"100.00","2":"200.00","3":"300.00"}'}:id==='transaction-account-owners'?{textContent:'{"1":1,"2":1,"3":2}'}:null,
querySelector:()=>({form}),createElement:()=>({setAttribute(){},classList:classes()})},Event:class{},setTimeout};
vm.runInNewContext(fs.readFileSync('public/assets/transaction-validation.js','utf8'),context);
let checks=0;
function check(value){assert.ok(value);checks++;}
function change(name,value){controls[name].value=value;controls[name].listeners.change();}
change('account_id','1');change('amount','150');change('type','transfer');
check(controls.account_id.options[1].disabled);
check(controls.destination_account_id.options[1].disabled);
check(controls.destination_account_id.options[3].disabled);
check(!!controls.account_id.error);
change('amount','50');
check(!controls.account_id.options[1].disabled && !controls.account_id.error);
change('destination_account_id','2');change('account_id','2');
check(controls.destination_account_id.value==='' && !!controls.destination_account_id.error);
change('type','income');
check(controls.destination_account_id.disabled && !controls.destination_account_id.error);
change('amount','0.001');check(!!controls.amount.error);
change('amount','0');check(!!controls.amount.error);
change('amount','300');change('type','adjustment');change('direction','decrease');
check(!!controls.account_id.error);
change('direction','increase');check(!controls.account_id.error);
change('transaction_date','2026-10-02');check(!!controls.transaction_date.error);
change('transaction_date','2026-10-01');check(!controls.transaction_date.error);
change('description',' ');check(!!controls.description.error);
change('description','Valid');check(!controls.description.error);
change('amount','9999999999999.99');check(!!controls.account_id.error);
console.log('Passed '+checks+' live validation checks.');