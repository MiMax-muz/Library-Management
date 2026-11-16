import {currentUser,refreshSession} from '../shared/session.js';
import {setCsrfToken} from '../shared/api.js';
import {request} from '../shared/api.js';
import {data,form,field,heading} from '../shared/forms.js';
import {paragraph} from '../shared/actions.js';
export async function mount({container,signal}){
 const u=currentUser();container.replaceChildren();heading(container,'Tài khoản của tôi');paragraph(container,'Email: '+u.email);paragraph(container,'Vai trò: '+({user:'Độc giả',librarian:'Nhân viên',admin:'Quản trị viên'})[u.vai_tro]);
 heading(container,'Đổi mật khẩu');form(container,[field('old_password','Mật khẩu hiện tại','password'),field('password','Mật khẩu mới','password'),field('confirm','Xác nhận mật khẩu mới','password')],{},async d=>{if(d.password!==d.confirm)throw new Error('Mật khẩu xác nhận không khớp.');const r=await request('/accounts/password',{method:'POST',body:d,signal});setCsrfToken(r.data.csrf_token);alert('Đã đổi mật khẩu.');await mount({container,signal});},'Đổi mật khẩu');
 if(u.vai_tro!=='user'){logoutFooter(container);return;}
 const r=await data('/profile',signal);if(signal.aborted)return;heading(container,'Hồ sơ cá nhân');
 form(container,[field('ho_ten','Họ tên'),field('ngay_sinh','Ngày sinh','date',false),field('dien_thoai','Điện thoại','tel',false),field('dia_chi','Địa chỉ','text',false)],r.profile,async d=>{await request('/profile',{method:'PUT',body:d,signal});await mount({container,signal});},'Lưu hồ sơ');
 heading(container,'Thẻ thư viện');const p=r.profile;paragraph(container,p.ma_the?`Mã thẻ: ${p.ma_the} · Hạn: ${p.ngay_het_han} · ${r.eligibility.eligible?'Đủ điều kiện mượn':'Chưa đủ điều kiện mượn'}`:'Chưa được cấp thẻ. Lưu hồ sơ và liên hệ nhân viên thư viện để cấp thẻ.');
 if(p.ma_the&&!r.eligibility.eligible)paragraph(container,({CARD_LOCKED:'Thẻ đang bị khóa.',CARD_EXPIRED:'Thẻ chưa có hiệu lực hoặc đã hết hạn.',ACCOUNT_LOCKED:'Tài khoản đang bị khóa.'})[r.eligibility.reason]||'Liên hệ nhân viên để kiểm tra thẻ.');
 logoutFooter(container);
}

function logoutFooter(container){
 const footer=document.createElement('div');footer.className='profile-footer';
 const status=document.createElement('p');status.setAttribute('role','alert');
 const button=document.createElement('button');button.type='button';button.className='btn profile-logout';button.textContent='Đăng xuất';
 button.onclick=async()=>{button.disabled=true;status.textContent='';try{await request('/accounts/logout',{method:'POST',body:{logout:true}});setCsrfToken(null);await refreshSession();location.hash='/login';}catch(e){status.textContent=e.message;button.disabled=false;}};
 footer.append(status,button);container.append(footer);
}
