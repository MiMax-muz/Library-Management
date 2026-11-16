import {button} from './forms.js';
export function action(host,label,fn){const b=button(host,label,async()=>{b.disabled=true;try{await fn();}catch(e){if(e.name!=='AbortError')alert(e.message);}finally{b.disabled=false;}});return b;}
export function paragraph(host,text){const p=document.createElement('p');p.textContent=text;host.append(p);return p;}
export function link(host,label,path){const a=document.createElement('a');a.className='btn outline';a.textContent=label;a.href=path;host.append(a);return a;}
export const states={pending:'Chờ duyệt',approved:'Đã duyệt',rejected:'Từ chối',cancelled:'Đã hủy',handed_over:'Đã bàn giao',borrowed:'Đang mượn',returned:'Đã trả',lost:'Mất',damaged:'Hỏng',available:'Khả dụng'};
