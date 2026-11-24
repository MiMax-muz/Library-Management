import {request} from '../shared/api.js';
import {data,table,heading} from '../shared/forms.js';
import {action,paragraph} from '../shared/actions.js';

export async function mount({container,signal}){
 const catalog=await data('/books',signal);
 async function render(active){
  const rows=await data('/shelves',signal);
  if(signal.aborted)return;
  const shelves=new Map();
  for(const row of rows){
   if(!shelves.has(row.id))shelves.set(row.id,{id:row.id,name:row.name,books:[]});
   if(row.sach_id)shelves.get(row.id).books.push(row);
  }
  container.replaceChildren();
  const gallery=document.createElement('section');gallery.className='shelf-gallery';container.append(gallery);
  const tools=document.createElement('div');tools.className='collection-tools';gallery.append(tools);
  const query=document.createElement('input');query.type='search';query.placeholder='Tìm tủ hoặc tên sách…';query.setAttribute('aria-label','Tìm trong tủ sách cá nhân');tools.append(query);
  function editShelf(mode,shelf){
   gallery.querySelector('.shelf-editor')?.remove();
   const deleting=mode==='delete', box=document.createElement('form');
   box.className='panel shelf-editor';
   (shelf?panel:tools).after(box);
   heading(box,deleting?'Xóa tủ sách':shelf?'Đổi tên tủ':'Tạo tủ sách');
   const input=document.createElement('input');
   if(deleting)paragraph(box,'Xóa tủ “'+shelf.name+'”? Các sách vẫn còn trong thư viện.');
   else{
    const label=document.createElement('label');label.className='field';label.textContent='Tên tủ sách';
    input.className='input';input.required=true;input.maxLength=100;input.value=shelf?.name||'';
    label.append(input);box.append(label);
   }
   const error=document.createElement('p');error.setAttribute('role','alert');
   const save=document.createElement('button'),cancel=document.createElement('button');
   save.className='btn';save.textContent=deleting?'Xóa tủ':shelf?'Lưu tên':'Tạo tủ';
   cancel.type='button';cancel.className='btn outline';cancel.textContent='Hủy';
   cancel.onclick=()=>box.remove();box.append(error,save,cancel);
   (deleting?cancel:input).focus();if(!deleting)input.select();
   box.onsubmit=async event=>{
    event.preventDefault();
    const name=input.value.trim();
    if(!deleting&&!name){error.textContent='Vui lòng nhập tên tủ.';input.focus();return;}
    save.disabled=cancel.disabled=true;
    try{
     const result=await request(shelf?'/shelves/'+shelf.id:'/shelves',{method:deleting?'DELETE':shelf?'PUT':'POST',...(deleting?{}:{body:{name}}),signal});
     await render(deleting?undefined:shelf?.id??result.data.id);
    }catch(e){if(!signal.aborted){error.textContent=e.message;save.disabled=cancel.disabled=false;}}
   };
  }
  action(tools,'+ Tạo tủ',()=>editShelf('create'));
  const nav=document.createElement('div'),panel=document.createElement('section');
  nav.className='collection-grid';panel.className='panel';panel.hidden=true;gallery.append(nav,panel);
  function open(shelf){
   gallery.querySelector('.shelf-editor')?.remove();nav.hidden=true;tools.hidden=true;panel.hidden=false;panel.replaceChildren();
   action(panel,'← Các tủ sách', ()=>{gallery.querySelector('.shelf-editor')?.remove();panel.hidden=true;nav.hidden=false;tools.hidden=false;query.focus();});
   const path='/shelves/'+shelf.id;
   const title=document.createElement('header');title.className='collection-title';panel.append(title);
   const rename=action(title,'✎',()=>editShelf('rename',shelf));
   rename.title='Đổi tên tủ';rename.setAttribute('aria-label','Đổi tên tủ '+shelf.name);heading(title,shelf.name);
   table(panel,shelf.books,[['ten_sach','Tên sách']],[['Xem sách',b=>window.open('#/books/'+b.sach_id,'_blank','noopener')],['Bỏ khỏi tủ',async b=>{
    await request(path+'/books/'+b.sach_id,{method:'DELETE',signal});await render(shelf.id);
   }]]);
   const footer=document.createElement('footer');footer.className='collection-footer';panel.append(footer);
   action(footer,'Xóa tủ',()=>editShelf('delete',shelf));

  }
  function draw(){
   nav.replaceChildren();
   const term=query.value.trim().toLocaleLowerCase('vi');
   for(const shelf of shelves.values()){
    if(![shelf.name,...shelf.books.map(b=>b.ten_sach)].some(name=>name.toLocaleLowerCase('vi').includes(term)))continue;
    const tile=document.createElement('button');tile.type='button';tile.className='collection-tile';tile.onclick=()=>open(shelf);
    const art=document.createElement('span');art.className='collection-art';art.style.setProperty('--tone',String((Number(shelf.id)*47+116)%360));
    art.textContent=({ 'Yêu thích':'♡','Học thuật':'▤','Giải trí':'✧' })[shelf.name]||shelf.name.slice(0,1).toLocaleUpperCase('vi');
    const covers=shelf.books.map(b=>catalog.find(c=>String(c.sach_id)===String(b.sach_id))).map(c=>c?.cover_image_url||c?.cover_path).filter(src=>src&&/^(https?:\/\/|\/assets\/covers\/)/i.test(src)).slice(0,4);
    if(covers.length){art.textContent='';for(const src of covers){const img=document.createElement('img');img.src=src;img.alt='';img.loading='lazy';art.append(img);}}
    const name=document.createElement('strong'),count=document.createElement('small');name.textContent=shelf.name;count.textContent=shelf.books.length+' cuốn sách';
    tile.append(art,name,count);nav.append(tile);
   }
   if(!nav.childElementCount)paragraph(nav,term?'Không tìm thấy tủ phù hợp.':'Bạn chưa có tủ sách.');
  }
  query.oninput=draw;draw();
  const selected=[...shelves.values()].find(s=>String(s.id)===String(active));if(selected)open(selected);
 }
 await render();
}
