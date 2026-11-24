import {data} from '../shared/forms.js';
import {request} from '../shared/api.js';

function element(tag,text,className){
 const el=document.createElement(tag);
 if(text)el.textContent=text;
 if(className)el.className=className;
 return el;
}
export function borrowCatalog(host,books,policy,signal,refresh){
 const limit=Number(policy.borrow_per_request), selected=new Map(),holds=new Set();
 const bar=element('div',null,'borrow-tools'),input=element('input',null,'input');
 input.type='search';input.placeholder='Tìm kiếm sách…';input.setAttribute('aria-label','Tìm theo tên hoặc mã sách');
 const toggle=element('button',null,'btn'),title=element('h2'),grid=element('div',null,'borrow-grid');
 const drawer=element('dialog',null,'borrow-drawer');
 drawer.setAttribute('aria-label','Phiếu mượn sách');
 const status=element('p');status.setAttribute('role','status');
 bar.append(input,toggle);host.append(bar,title,grid,status,drawer);
 let sending=false;
 const normalize=s=>s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replaceAll('đ','d');
 function cards(){
  grid.replaceChildren();toggle.textContent='Phiếu mượn ('+selected.size+'/'+limit+')';
  const q=input.value.trim();title.textContent=q?'Kết quả tìm kiếm: '+q:'Chọn sách để mượn';
  for(const book of books.filter(b=>normalize(b.ten_sach+' '+b.ma_sach).includes(normalize(q)))){
   const card=element('article',null,'panel borrow-card'),url=book.cover_image_url||book.cover_path;
   if(url&&/^(https?:\/\/|\/assets\/covers\/)/i.test(url)){const img=element('img');img.src=url;img.alt='Bìa '+book.ten_sach;card.append(img);}
   const available=Number(book.available)>0,added=selected.has(book.sach_id);
   const btn=element('button',available?(added?'✓ Đã thêm':'+ Thêm vào phiếu'):(holds.has(book.sach_id)?'Đã chọn giữ chỗ (thử)':'Đặt giữ chỗ (thử)'), 'btn outline');
   btn.disabled=available&&(added||selected.size>=limit);
   btn.onclick=async()=>{
    if(!available){holds.add(book.sach_id);status.textContent='Đã chọn giữ chỗ thử cho '+book.ten_sach+'. Lựa chọn chỉ lưu trong màn hình này.';cards();return;}
    btn.disabled=true;
    try{
     const detail=await data('/books/'+book.sach_id,signal);
     if(signal.aborted||sending)return;
     const copy=detail.copies.find(c=>c.tinh_trang==='available'&&Number(c.dang_luu_hanh));
     if(!copy){book.available=0;status.textContent='Đầu sách vừa hết bản.';}
     else if(selected.size<limit)selected.set(book.sach_id,{title:book.ten_sach,id:copy.ban_sach_id});
     cards();if(drawer.open)basket();
    }catch(e){if(!signal.aborted){status.textContent=e.message;btn.disabled=false;}}
   };
   card.append(element('h3',book.ten_sach),element('p',available?'Còn '+book.available+' cuốn':'Hết bản'),btn);grid.append(card);
  }
  if(!grid.childElementCount)grid.append(element('p','Không tìm thấy sách.'));
 }
 function basket(){
  drawer.replaceChildren();
  const close=element('button','Đóng','btn outline');close.onclick=()=>drawer.close();
  drawer.append(close,element('h2','Phiếu mượn sách'),element('p','Hạn mức: '+selected.size+' / '+limit+' cuốn'));
  for(const [key,book] of selected){
   const row=element('section',null,'panel'),remove=element('button','Bỏ chọn','btn outline');
   remove.onclick=()=>{selected.delete(key);cards();basket();};
   row.append(element('strong',book.title),element('p','Bản in · Thủ thư sẽ ghi hạn trả khi bàn giao'),remove);drawer.append(row);
  }
  const submit=element('button','Xác nhận mượn ('+selected.size+')','btn'),feedback=element('p');
  feedback.setAttribute('role','status');submit.disabled=!selected.size||sending;
  submit.onclick=async()=>{
   if(sending)return;sending=true;drawer.querySelectorAll('button').forEach(b=>b.disabled=true);
   try{
    await request('/loans/requests',{method:'POST',body:{copy_ids:[...selected.values()].map(b=>b.id)},signal});
    selected.clear();drawer.close();await refresh();
   }catch(e){if(!signal.aborted){sending=false;basket();drawer.append(element('p',e.message));}}
  };
  drawer.append(element('p','Phiếu đủ điều kiện được duyệt tự động. Vui lòng nhận sách tại quầy.'),submit,feedback);
 }
 drawer.oncancel=e=>{if(sending)e.preventDefault();};
 toggle.onclick=()=>{basket();drawer.showModal();};input.oninput=cards;cards();
}
