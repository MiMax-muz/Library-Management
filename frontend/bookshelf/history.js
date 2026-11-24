import {data,heading,table} from '../shared/forms.js';
const localTime=value=>new Date(value.replace(' ','T')+'Z').toLocaleString('vi-VN',{timeZone:'Asia/Ho_Chi_Minh'});
export async function mount({container,signal}){
 const {viewed,borrowed}=await data('/book-history',signal);
 if(signal.aborted)return;
 container.replaceChildren();
 function section(title,rows,columns,empty){
  const panel=document.createElement('section');
  panel.className='panel';
  heading(panel,title);
  table(panel,rows,columns,[['Xem sách',r=>window.open('#/books/'+r.sach_id,'_blank','noopener')]]);
  if(!rows.length)panel.querySelector('tbody td').textContent=empty;
  container.append(panel);
 }
 section('Sách đã xem',viewed.map(r=>({...r,time:localTime(r.viewed_at)})),[['ten_sach','Tên sách'],['time','Lần xem gần nhất']],'Bạn chưa xem chi tiết đầu sách nào.');
 section('Sách đã mượn',borrowed.map(r=>({...r,time:localTime(r.borrowed_at),copy_code:r.copy_code||'—'})),[['ten_sach','Tên sách'],['kind','Loại sách'],['copy_code','Mã bản'],['time','Ngày giờ mượn']],'Bạn chưa có lượt mượn sách.');
}
