import {currentUser} from '../shared/session.js';
import {request} from '../shared/api.js';
import {form,field,select,heading,table,data} from '../shared/forms.js';
import {paymentView} from "./payment.js";
export async function mount({container,signal}){

 container.replaceChildren();if(currentUser().vai_tro!=='user'){
 heading(container,'Tính phí trả sách quá hạn');
 const note=document.createElement('p');note.textContent='Tiền phạt = số ngày quá hạn × số cuốn × đơn giá mỗi ngày. Trả đúng hạn hoặc sớm hơn không bị phạt. Các cuốn có hạn trả khác nhau cần tính riêng.';container.append(note);
 const result=document.createElement('p');result.setAttribute('role','status');
 form(container,[field('due_date','Hạn trả sách','date'),field('return_date','Ngày trả thực tế','date'),field('count','Số cuốn cùng hạn trả','number')],{count:1,return_date:new Intl.DateTimeFormat('en-CA',{timeZone:'Asia/Ho_Chi_Minh'}).format(new Date())},async d=>{
 const {data:r}=await request('/fines/preview',{method:'POST',body:{...d,violation:'overdue'},signal});if(signal.aborted)return;
 result.textContent=r.days+' ngày × '+r.count+' cuốn × '+Number(r.policy.overdue_daily_vnd).toLocaleString('vi-VN')+' đ = '+r.overdue.toLocaleString('vi-VN')+' đ. '+(r.days===0?'Không phát sinh phạt quá hạn.':'Đây là khoản tính dự kiến.');
 },'Tính phí quá hạn');container.append(result);
 const link=document.createElement('a');link.className='btn outline';link.href='#/loans';link.textContent='Mở phiếu mượn để xác nhận trả và ghi phạt';container.append(link);
 }

 const rows=await data('/fines',signal);if(signal.aborted)return;
 if(currentUser().vai_tro==='user'){await paymentView(container,rows,signal);return;}
 const types={overdue:'Quá hạn',lost:'Mất sách',damaged:'Hỏng không phục hồi',repair:'Hỏng có thể sửa'};
 const money=value=>Number(value||0).toLocaleString('vi-VN')+' đ';
 heading(container,'Các khoản phạt đã xác nhận');
 const paidRows=rows.filter(r=>r.payment_id);const dueRows=rows.filter(r=>!r.payment_id);
 const summary=document.createElement('p');summary.textContent=rows.length+' khoản đã ghi nhận · Còn phải thu: '+money(dueRows.reduce((n,r)=>n+Number(r.total),0))+' · Đã thu: '+money(paidRows.reduce((n,r)=>n+Number(r.paid_amount),0));container.append(summary);
 const list=document.createElement('section'),detail=document.createElement('section');
 const draw=items=>{list.replaceChildren();table(list,items.map(r=>({...r,type_label:types[r.violation]||r.violation,amount:money(r.total),payment_state:r.payment_id?'Đã thu':'Chưa thu',paid_label:r.paid_at?money(r.paid_amount)+' · '+r.paid_at+' UTC':'—'})),[['fine_id','Mã'],['ho_ten','Độc giả'],['ten_sach','Sách'],['ma_ban_sach','Bản sách'],['type_label','Vi phạm'],['amount','Số tiền'],['payment_state','Thanh toán'],['paid_label','Lịch sử thu']],[['Chi tiết / thu tiền',async r=>{detail.replaceChildren();heading(detail,'Chi tiết khoản #'+r.fine_id);const calc=JSON.parse(r.calculation_json);for(const text of ['Độc giả: '+r.ho_ten,'Vi phạm: '+r.type_label,'Số ngày quá hạn: '+calc.days,'Phạt quá hạn: '+money(calc.overdue),'Bồi thường: '+money(calc.compensation),'Tổng: '+money(r.total),'Căn cứ sửa chữa: '+(r.evidence||'Không có'),r.payment_id?'Đã thu '+money(r.paid_amount)+' lúc '+r.paid_at+' UTC':'Chưa thu']){const p=document.createElement('p');p.textContent=text;detail.append(p);}if(currentUser().vai_tro!=='user'&&!r.payment_id&&Number(r.total)>0){const button=document.createElement('button');button.className='btn';button.type='button';const methodSelect=document.createElement('select');methodSelect.className='select';methodSelect.setAttribute('aria-label','Hình thức thanh toán');methodSelect.add(new Option('Tiền mặt','cash'));methodSelect.add(new Option('Chuyển khoản đã đối soát','transfer'));detail.append(methodSelect);button.textContent='Xác nhận đã nhận tiền';button.onclick=async()=>{const method=methodSelect.value;if(!confirm('Xác nhận đã nhận đủ '+money(r.total)+'?'))return;button.disabled=true;try{const result=await request('/fines/'+r.fine_id+'/pay',{method:'POST',body:{method},signal});alert(result.data.already_paid?'Khoản này đã được thu trước đó.':'Đã ghi nhận thanh toán đầy đủ.');await mount({container,signal});}catch(e){alert(e.message);}finally{button.disabled=false;}};detail.append(button);}}]]);};
 form(container,[field('q','Tìm độc giả / sách / mã bản','text',false),select('type','Loại vi phạm',[['all','Tất cả'],...Object.entries(types)])],{},async d=>draw(rows.filter(r=>(d.type==='all'||r.violation===d.type)&&(r.ho_ten+' '+r.ten_sach+' '+r.ma_ban_sach).toLocaleLowerCase('vi').includes(d.q.toLocaleLowerCase('vi')))),'Lọc danh sách');container.append(list,detail);draw(rows);
}
