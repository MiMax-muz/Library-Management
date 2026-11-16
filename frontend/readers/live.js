import {request} from '../shared/api.js';
import {form,field,select,data,table,heading,button} from '../shared/forms.js';
export async function mount({container,signal}){
 const [rows,accounts]=await Promise.all([data('/readers',signal),data('/readers/accounts',signal)]);if(signal.aborted)return;container.replaceChildren();heading(container,'Độc giả và thẻ thư viện');
 const editor=document.createElement('section');const list=document.createElement('section');container.append(list,editor);
 const edit=(row={})=>{editor.replaceChildren();heading(editor,row.doc_gia_id?'Sửa hồ sơ':'Thêm hồ sơ');
 form(editor,[select('tai_khoan_id','Tài khoản User',accounts.map(a=>[a.tai_khoan_id,a.email+' · '+a.mssv])),field('ho_ten','Họ tên'),field('ngay_sinh','Ngày sinh','date',false),field('dien_thoai','Điện thoại','tel',false),field('dia_chi','Địa chỉ','text',false)],row,async d=>{await request('/readers'+(row.doc_gia_id?'/'+row.doc_gia_id:''),{method:row.doc_gia_id?'PUT':'POST',body:d,signal});await mount({container,signal});});
 if(row.doc_gia_id){heading(editor,'Thẻ độc giả');
 const act=async d=>{await request('/readers/'+row.doc_gia_id+'/card',{method:'POST',body:d,signal});await mount({container,signal});};
 if(!row.ma_the)form(editor,[field('ma_the','Mã thẻ')],{},d=>act({...d,action:'issue'}),'Cấp thẻ 12 tháng');
 else{button(editor,'Gia hạn 12 tháng',()=>{if(confirm('Gia hạn thẻ thêm 12 tháng?'))act({action:'renew'}).catch(e=>alert(e.message));});button(editor,row.card_status==='active'?'Khóa thẻ':'Mở khóa thẻ',()=>act({action:row.card_status==='active'?'lock':'unlock'}).catch(e=>alert(e.message)));}
 button(editor,'Kiểm tra hiệu lực',async()=>{try{const r=await data('/readers/'+row.doc_gia_id+'/eligibility',signal);alert(r.eligible?'Thẻ đủ điều kiện sử dụng':'Không đủ điều kiện: '+r.reason);}catch(e){alert(e.message);}});
 }};
 button(list,'Thêm độc giả',()=>edit());const search=document.createElement('input');search.className='input';search.placeholder='Tìm tên hoặc mã thẻ';search.setAttribute('aria-label','Tìm độc giả');list.append(search);const results=document.createElement('div');list.append(results);
 const draw=()=>{results.replaceChildren();const q=search.value.toLowerCase();table(results,rows.filter(r=>(r.ho_ten+' '+r.ma_the).toLowerCase().includes(q)),[['ho_ten','Họ tên'],['ma_the','Mã thẻ'],['ngay_het_han','Hết hạn'],['card_status','Trạng thái']],[['Hồ sơ / thẻ',edit]]);};search.oninput=draw;draw();
}
