import {request} from './api.js';
export const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
export const field=(key,label,type='text',required=true)=>({key,label,type,required});
export const select=(key,label,options)=>({key,label,options,required:true});
export function form(host,fields,values,onSave,label='Lưu'){
 const f=document.createElement('form');f.className='panel';
 f.innerHTML=fields.map(x=>'<label class="field">'+esc(x.label)+(x.options?'<select class="select" name="'+x.key+'">'+x.options.map(([v,t])=>'<option value="'+esc(v)+'" '+(String(values[x.key]??'')===String(v)?'selected':'')+'>'+esc(t)+'</option>').join('')+'</select>':x.type==='textarea'?'<textarea class="input" rows="4" name="'+x.key+'">'+esc(values[x.key])+'</textarea>':'<input class="input" name="'+x.key+'" type="'+(x.type||'text')+'" value="'+esc(values[x.key])+'" '+(x.required?'required':'')+'>')+'</label>').join('')+'<p role="alert"></p><button class="btn">'+label+'</button>';
 f.onsubmit=async e=>{e.preventDefault();const button=f.querySelector('button');button.disabled=true;const d=Object.fromEntries(new FormData(f));for(const x of fields)if(x.type==='number')d[x.key]=d[x.key]===''?null:Number(d[x.key]);try{await onSave(d);}catch(err){f.querySelector('[role=alert]').textContent=err.message;}finally{button.disabled=false;}};
 host.append(f);return f;
}
export async function data(path,signal){return (await request(path,{signal})).data;}
export function table(host,rows,columns,actions=[]){
 const wrap=document.createElement('div');wrap.className='table-wrap';const t=document.createElement('table');t.innerHTML='<thead><tr>'+columns.map(([,title])=>'<th>'+esc(title)+'</th>').join('')+(actions.length?'<th>Thao tác</th>':'')+'</tr></thead>';const body=document.createElement('tbody');
 for(const row of rows){const tr=document.createElement('tr');tr.innerHTML=columns.map(([key])=>'<td>'+esc(row[key])+'</td>').join('');if(actions.length){const td=document.createElement('td');for(const [label,fn]of actions){const b=document.createElement('button');b.className='btn outline';b.textContent=label;b.onclick=async()=>{b.disabled=true;try{await fn(row);}catch(e){alert(e.message);}finally{b.disabled=false;}};td.append(b);}tr.append(td);}body.append(tr);}
 if(!rows.length){const tr=document.createElement('tr');const td=document.createElement('td');td.colSpan=columns.length+1;td.textContent='Chưa có dữ liệu.';tr.append(td);body.append(tr);}
 t.append(body);wrap.append(t);host.append(wrap);
}
export function heading(host,text){const h=document.createElement('h2');h.textContent=text;host.append(h);}
export function button(host,label,fn){const b=document.createElement('button');b.className='btn outline';b.textContent=label;b.onclick=fn;host.append(b);return b;}
