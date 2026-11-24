import {request} from '../shared/api.js';
import {currentUser} from '../shared/session.js';
import {form,field,select,data,table,heading,button} from '../shared/forms.js';
const metadata=Object.entries({cover_image_url:'Đường dẫn ảnh bìa',subtitle:'Phụ đề',publish_place:'Nơi xuất bản',edition:'Lần tái bản',issn:'ISSN',languages:'Ngôn ngữ',subjects:'Chủ đề',tags:'Từ khóa',table_of_contents:'Mục lục'});
function cover(book,large=false){const img=document.createElement('img');img.className=large?'book-cover book-cover-large':'book-cover';img.alt='Bìa '+book.ten_sach;img.loading='lazy';if(/^https?:\/\//i.test(book.cover_image_url||'')){img.src=book.cover_image_url;return img;}if(!/^\/assets\/covers\/[a-zA-Z0-9_-]+\.svg$/.test(book.cover_image_url||book.cover_path||''))return null;img.src=book.cover_image_url||book.cover_path;return img;}
export async function mount({container,signal,params={}}){
 const [books,categories,locations,authors,publishers]=await Promise.all([params.id?null:'/books','/categories','/locations','/authors','/publishers'].map(p=>p?data(p,signal):[]));if(signal.aborted)return;container.replaceChildren();
 const manage=['admin','librarian'].includes(currentUser().vai_tro);if(!params.id)heading(container,'Danh mục sách');const controls=document.createElement('section'),results=document.createElement('section'),editor=document.createElement('section');container.append(controls,results,editor);
 const opts=categories.map(c=>[c.the_loai_id,c.ten_the_loai]);const active=select('dang_luu_hanh','Lưu hành',[[1,'Có'],[0,'Ngừng']]);
 const edit=(row={})=>{editor.replaceChildren();heading(editor,row.sach_id?'Sửa đầu sách':'Thêm đầu sách');form(editor,[field('ma_sach','Mã sách'),field('ten_sach','Tên sách'),select('the_loai_id','Thể loại',opts),field('isbn','ISBN','text',false),select('nha_xuat_ban_id','Nhà xuất bản',[['','Chưa chọn'],...publishers.map(p=>[p.nha_xuat_ban_id,p.ten_nha_xuat_ban])]),field('nam_xuat_ban','Năm xuất bản','number',false),field('gia_bia','Giá bìa','number',false),field('mo_ta','Tóm tắt nội dung','textarea',false),...metadata.map(([key,label])=>field(key,label,key==='table_of_contents'?'textarea':'text',false)),...authors.map(a=>field('role_'+a.tac_gia_id,'Vai trò của '+a.ho_ten+' (bỏ trống nếu không tham gia)','text',false)),active],{...row,...Object.fromEntries((row.authors||[]).map(a=>['role_'+a.tac_gia_id,a.author_role||'Tác giả']))},async d=>{d.nha_xuat_ban_id=d.nha_xuat_ban_id?Number(d.nha_xuat_ban_id):null;d.author_ids=authors.filter(a=>d['role_'+a.tac_gia_id].trim()).map(a=>a.tac_gia_id);d.author_roles=Object.fromEntries(d.author_ids.map(id=>[id,d['role_'+id].trim()]));await request('/books'+(row.sach_id?'/'+row.sach_id:''),{method:row.sach_id?'PUT':'POST',body:d,signal});await mount({container,signal,params});});};
 const detail=async row=>{const book=await data('/books/'+row.sach_id,signal);if(signal.aborted)return;editor.replaceChildren();heading(editor,'Chi tiết sách');const panel=document.createElement('section');panel.className='panel catalog-record';const image=cover(book,true);if(image)panel.append(image);const info=document.createElement('dl');
 const publisher=publishers.find(p=>String(p.nha_xuat_ban_id)===String(book.nha_xuat_ban_id));
 for(const [label,value] of [
  ['Nhan đề',[book.ten_sach,book.subtitle].filter(Boolean).join(' : ')],['Tác giả / người biên soạn',book.authors.map(a=>a.ho_ten+' ('+a.author_role+')').join('\n')],
  ['Nhà xuất bản',publisher?.ten_nha_xuat_ban],['Nơi xuất bản',book.publish_place],['Năm xuất bản',book.nam_xuat_ban],
  ['Lần tái bản',book.edition],['ISBN',book.isbn],...(book.issn?[['ISSN',book.issn]]:[]),['Ngôn ngữ',book.languages],['Chủ đề',book.subjects],['Từ khóa',book.tags],
  ['Tóm tắt nội dung',book.mo_ta],['Mục lục',book.table_of_contents]
 ]){
  const term=document.createElement('dt'),entry=document.createElement('dd');
  term.textContent=label;entry.textContent=value||'Chưa cập nhật';entry.style.whiteSpace='pre-line';info.append(term,entry);
 }
 panel.append(info);editor.append(panel);editor.scrollIntoView({block:'start'});
 if(!manage && book.ebook_ids?.length){
  const digital=document.createElement('section');digital.className='panel';editor.append(digital);
  heading(digital,'Bản điện tử');
  for(const id of book.ebook_ids){
   const ebook=await data('/ebooks/'+id,signal);
   if(signal.aborted)return;
   const status=document.createElement('p');status.setAttribute('role','status');digital.append(status);
   if(!ebook.versions.length){status.textContent='Chưa có bản PDF.';continue;}
   if(ebook.can_read){
    for(const file of ebook.versions){
     const read=document.createElement('a');read.className='btn';read.href='#/reader/'+file.ebook_file_id;
     read.textContent='Đọc trực tuyến · Phiên bản '+file.file_version;digital.append(read);
    }
   }else{
    status.textContent='Cần quyền mượn để đọc bản PDF.';
    const borrow=document.createElement('button');borrow.className='btn';borrow.textContent='Yêu cầu mượn eBook';digital.append(borrow);
    borrow.onclick=async()=>{
     borrow.disabled=true;
     try{await request('/ebook-access',{method:'POST',body:{ebook_ids:[ebook.ebook_id]},signal});status.textContent='Đã gửi yêu cầu mượn eBook, đang chờ duyệt.';}
     catch(error){if(!signal.aborted){status.textContent=error.message;borrow.disabled=false;}}
    };
   }
  }
 } if(manage){button(editor,'Sửa đầu sách',()=>edit(book));button(editor,'Xóa đầu sách',async()=>{if(confirm('Xóa đầu sách này?'))try{await request('/books/'+book.sach_id,{method:'DELETE',signal});location.hash='/books';}catch(e){alert(e.message);}});}
 const copyEditor=document.createElement('section');
 const copy=(c={})=>{copyEditor.replaceChildren();heading(copyEditor,'Thông tin bản sách');form(copyEditor,[field('ma_ban_sach','Mã bản sách'),select('vi_tri_id','Vị trí',locations.filter(l=>Number(l.the_loai_id)===Number(book.the_loai_id)).map(l=>[l.vi_tri_id,l.ma_vi_tri])),select('tinh_trang','Tình trạng',[['available','Khả dụng'],['damaged','Hỏng'],['lost','Mất']]),active],c,async d=>{await request('/books/'+book.sach_id+'/copies'+(c.ban_sach_id?'/'+c.ban_sach_id:''),{method:c.ban_sach_id?'PUT':'POST',body:d,signal});await detail(book);});};
 table(editor,book.copies,[['ma_ban_sach','Mã bản'],['ma_vi_tri','Vị trí'],['tinh_trang','Tình trạng'],['dang_luu_hanh','Lưu hành']],manage?[['Sửa',copy],['Xóa',async c=>{if(confirm('Xóa bản sách?')){await request('/copies/'+c.ban_sach_id,{method:'DELETE',signal});await detail(book);}}]]:[]);
 if(manage)button(editor,'Thêm bản sách',()=>copy());editor.append(copyEditor);
 };
 if(params.id){if(!/^[1-9][0-9]*$/.test(params.id))throw new Error('Mã đầu sách không hợp lệ.');await detail({sach_id:params.id});if(!signal.aborted)await request('/book-history/'+params.id+'/view',{method:'POST',body:{},signal});return;}
 const search=document.createElement('form');
 search.className='shelf-search';
 search.setAttribute('role','search');
 search.innerHTML='<input class="input" type="search" name="q" aria-label="Từ khóa tra cứu" placeholder="Tìm kiếm"><select class="select" name="resource" aria-label="Loại tài liệu"><option value="all">Tất cả tài liệu</option><option value="print">Tài liệu in</option><option value="digital">Tài liệu số</option><option value="online">Tài nguyên trực tuyến</option></select><button class="btn" type="submit">Tìm kiếm</button>';
 controls.append(search);
 let revision=0;
 async function filterBooks(){
  const current=++revision;
  try{
   const rows=await data('/books?'+new URLSearchParams(new FormData(search)),signal);
   if(current===revision&&!signal.aborted){
    draw(rows);
    if(!rows.length)results.textContent='Không tìm thấy tài liệu phù hợp.';
   }
  }catch(error){if(current===revision&&!signal.aborted)results.textContent=error.message;}
 }
 search.onsubmit=event=>{event.preventDefault();filterBooks();};
 search.elements.resource.onchange=filterBooks;
 const draw=rows=>{results.replaceChildren();table(results,rows,[['cover_path','Bìa'],['ma_sach','Mã sách'],['ten_sach','Tên sách'],['ten_the_loai','Thể loại'],['available','Khả dụng']],[['Chi tiết',book=>window.open('#/books/'+book.sach_id,'_blank','noopener')]]);results.querySelectorAll('tbody tr').forEach((tr,i)=>{if(!rows[i])return;const td=tr.cells[0],img=cover(rows[i]);td.replaceChildren();if(img)td.append(img);else td.textContent='Chưa có bìa';});};draw(books);
 if(!manage)return;button(controls,'Thêm sách',()=>edit());
 const catalogs=[['categories','Thể loại',categories,'the_loai_id',[field('ma_the_loai','Mã thể loại'),field('ten_the_loai','Tên thể loại')]],['locations','Vị trí',locations,'vi_tri_id',[select('the_loai_id','Thể loại',opts),field('ma_vi_tri','Mã vị trí (MA_THE_LOAI-KE)'),field('mo_ta','Mô tả')]],['authors','Tác giả',authors,'tac_gia_id',[field('ho_ten','Họ tên')]],['publishers','Nhà xuất bản',publishers,'nha_xuat_ban_id',[field('ten_nha_xuat_ban','Tên nhà xuất bản')]]];
 for(const [path,title,rows,key,fields] of catalogs)button(controls,title,()=>{editor.replaceChildren();heading(editor,title);const f=document.createElement('section');const change=(row={})=>{f.replaceChildren();form(f,fields,row,async d=>{await request('/'+path+(row[key]?'/'+row[key]:''),{method:row[key]?'PUT':'POST',body:d,signal});await mount({container,signal,params});});};button(editor,'Thêm '+title.toLowerCase(),()=>change());table(editor,rows,[[key,'ID'],...fields.map(x=>[x.key,x.label])],[['Sửa',change],['Xóa',async row=>{if(confirm('Xóa mục này?')){await request('/'+path+'/'+row[key],{method:'DELETE',signal});await mount({container,signal});}}]]);editor.append(f);});
}
