import {request} from '../shared/api.js';
import {data,form,field,select,table,heading} from '../shared/forms.js';
import {states,paragraph} from '../shared/actions.js';
export async function mount({container,signal}){
 const r=await data('/inventory',signal);if(signal.aborted)return;container.replaceChildren();paragraph(container,'Ghi nhận tình trạng thực tế của từng bản. Sách đang mượn phải xử lý bằng luồng trả sách, không đổi trực tiếp trong kiểm kê.');const editor=document.createElement('section');
 table(container,r.copies.map(b=>({...b,state:states[b.tinh_trang]})),[['ma_ban_sach','Bản sách'],['ten_sach','Sách'],['ma_vi_tri','Vị trí'],['state','Tình trạng']],[['Kiểm kê',b=>{editor.replaceChildren();heading(editor,b.ma_ban_sach);form(editor,[select('observed_status','Tình trạng thực tế',Object.entries(states).filter(([k])=>['available','borrowed','damaged','lost'].includes(k))),field('note','Ghi chú / căn cứ')],{observed_status:b.tinh_trang},async d=>{await request('/inventory',{method:'POST',body:{...d,ban_sach_id:b.ban_sach_id},signal});await mount({container,signal});},'Ghi nhận kiểm kê');}]]);container.append(editor);heading(container,'Lịch sử kiểm kê');table(container,r.checks,[['ma_ban_sach','Bản'],['observed_status','Tình trạng'],['note','Ghi chú'],['email','Người kiểm'],['created_at','Thời gian UTC']]);
}
