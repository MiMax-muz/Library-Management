import {request} from '../shared/api.js';
import {data,form,field,select,table,heading} from '../shared/forms.js';
import {paragraph,link} from '../shared/actions.js';
export async function mount({container,signal,favoritesOnly=false}){
 const all=await data('/bookshelf',signal);const rows=favoritesOnly?all.filter(r=>Number(r.favorite)===1):all;if(signal.aborted)return;container.replaceChildren();heading(container,favoritesOnly?'Tủ sách yêu thích':'Tủ sách của tôi');link(container,'Tìm thêm eBook','#/ebooks');const editor=document.createElement('section');
 const version=async id=>{const r=await data('/reading/'+id,signal);if(signal.aborted)return;editor.replaceChildren();heading(editor,'Tiến độ và dấu trang');paragraph(editor,'Thông tin lưu riêng theo tài khoản và phiên bản PDF. Dùng trình đọc để lưu và khôi phục vị trí.');link(editor,'Mở trình đọc PDF','#/reader/'+id);
 form(editor,[field('page_number','Trang đang đọc','number'),field('percent','Tiến độ (%)','number')],r.progress||{page_number:1,percent:0},async d=>{await request('/reading/'+id,{method:'PUT',body:d,signal});await version(id);},'Lưu tiến độ');
 form(editor,[field('page_number','Trang đánh dấu','number'),field('note','Ghi chú')],{page_number:r.progress?.page_number||1},async d=>{await request('/reading/'+id+'/bookmarks',{method:'POST',body:d,signal});await version(id);},'Lưu dấu trang');table(editor,r.bookmarks,[['page_number','Trang'],['note','Ghi chú']],[['Xóa',async b=>{await request('/bookmarks/'+b.bookmark_id,{method:'DELETE',signal});await version(id);}]]);
 };
 table(container,rows.map(r=>({...r,favorite_label:Number(r.favorite)?'Có':'Không'})),[['ten_sach','Sách'],['favorite_label','Yêu thích'],['expires_at','Hạn quyền đọc UTC']],[['Yêu thích / bỏ thích',async r=>{await request('/bookshelf/'+r.ebook_id,{method:'PUT',body:{favorite:!Number(r.favorite)},signal});await mount({container,signal,favoritesOnly});}],['Phiên bản / tiến độ',async r=>{const e=await data('/ebooks/'+r.ebook_id,signal);editor.replaceChildren();if(!e.can_read){paragraph(editor,'Quyền đọc không còn hiệu lực. Vào eBook để mượn lại.');return;}form(editor,[select('file','Phiên bản PDF',e.versions.map(v=>[v.ebook_file_id,'Phiên bản '+v.file_version]))],{},d=>version(d.file),'Mở tiến độ');}]]);container.append(editor);
 if(favoritesOnly)return;
 const history=await data('/reading-history',signal);if(signal.aborted)return;heading(container,'Lịch sử lưu tiến độ gần đây');table(container,history,[['ten_sach','Sách'],['file_version','Phiên bản'],['page_number','Trang'],['percent','%'],['created_at','Thời điểm UTC']]);
}
