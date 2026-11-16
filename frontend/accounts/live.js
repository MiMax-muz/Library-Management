import {request,setCsrfToken} from '../shared/api.js';
import {refreshSession,currentUser} from '../shared/session.js';
import {form,field,select,data,table,heading,button} from '../shared/forms.js';
const roles=[['user','User'],['librarian','Nhân viên thư viện'],['admin','Admin']];
export function mountLogin({container,signal,onLogin}){
 const message=text=>{const p=document.createElement('p');p.setAttribute('role','status');p.textContent=text;container.prepend(p);};
 const registered=(email,result)=>{
 container.replaceChildren();heading(container,'Xác thực tài khoản');
 const notice=document.createElement('section');notice.className='panel';notice.tabIndex=-1;
 const status=document.createElement('p');status.setAttribute('role','status');status.textContent=result.message;
 const recipient=document.createElement('p');recipient.textContent='Email nhận xác thực: '+email;
 const help=document.createElement('p');help.textContent='Kiểm tra hộp thư đến và thư rác, rồi bấm liên kết xác thực. Nếu chưa nhận được thư, chờ ít nhất 60 giây trước khi gửi lại.';
 notice.append(status,recipient,help);container.append(notice);
 form(container,[],{},async()=>{const r=await request('/accounts/resend-verification',{method:'POST',body:{email},signal});if(!signal.aborted)status.textContent=r.data.message;},'Gửi lại email xác thực');
 button(container,'Về đăng nhập',()=>draw());notice.focus();
 };
 const draw=(mode='login')=>{container.replaceChildren();const register=mode==='register',login=mode==='login';
 heading(container,({login:'Đăng nhập UniLib',register:'Đăng ký tài khoản',forgot:'Quên mật khẩu',resend:'Gửi lại email xác thực'})[mode]);
 const fields=register?[field('ho_ten','Họ tên'),field('email','Email','email'),field('password','Mật khẩu (8 ký tự trở lên)','password'),field('confirm_password','Nhập lại mật khẩu','password')]:login?[field('identifier','Email hoặc MSSV'),field('password','Mật khẩu','password')]:[field('email','Email đã đăng ký','email')];
 form(container,fields,{},async d=>{
 if(register&&d.password!==d.confirm_password)throw new Error('Mật khẩu xác nhận không khớp.');
 const endpoint=({login:'login',register:'register',forgot:'forgot-password',resend:'resend-verification'})[mode];
 const result=await request('/accounts/'+endpoint,{method:'POST',body:d,signal});if(signal.aborted)return;
 if(login){setCsrfToken(result.data.csrf_token);await refreshSession();if(!signal.aborted)onLogin();return;}
 if(register){registered(d.email,result.data);return;}
 draw(mode);message(result.data.message);
 },login?'Đăng nhập':register?'Đăng ký và gửi email':'Gửi liên kết');
 if(!login)button(container,'Về đăng nhập',()=>draw());
 if(login){button(container,'Đăng ký',()=>draw('register'));button(container,'Quên mật khẩu',()=>draw('forgot'));button(container,'Gửi lại email xác thực',()=>draw('resend'));button(container,'Đọc eBook công khai',()=>{location.hash='/public-ebooks';});}
 };draw();
}
export function mountEmailAction({container,signal,action,token}){
 container.replaceChildren();heading(container,action==='verify'?'Xác thực email':'Đặt lại mật khẩu');
 const fields=action==='verify'?[]:[field('password','Mật khẩu mới (8 ký tự trở lên)','password'),field('confirm_password','Nhập lại mật khẩu','password')];
 form(container,fields,{},async d=>{const r=await request('/accounts/'+(action==='verify'?'verify-email':'reset-password'),{method:'POST',body:{...d,token},signal});if(signal.aborted)return;container.replaceChildren();heading(container,r.data.message);button(container,'Về đăng nhập',()=>location.replace(location.pathname));},action==='verify'?'Xác nhận email':'Lưu mật khẩu mới');
 button(container,'Về đăng nhập',()=>location.replace(location.pathname));
}
export async function mount({container,signal}){
 container.replaceChildren();heading(container,'Quản lý tài khoản');
 if(currentUser().vai_tro!=='admin')return;
 const [accounts,employees]=await Promise.all([data('/accounts',signal),data('/employees',signal)]);if(signal.aborted)return;
 heading(container,'Tài khoản');const editor=document.createElement('section');container.append(editor);
 const edit=row=>{editor.replaceChildren();form(editor,row?[field('ho_ten','Họ tên'),field('dien_thoai','Điện thoại','tel',false),select('vai_tro','Vai trò',roles),select('trang_thai','Trạng thái',[['active','Hoạt động'],['locked','Khóa']])]:[field('ho_ten','Họ tên'),field('email','Email','email'),field('mssv','MSSV (User)','text',false),field('password','Mật khẩu','password'),select('vai_tro','Vai trò',roles)],row||{},async d=>{await request('/accounts'+(row?'/'+row.tai_khoan_id:''),{method:row?'PATCH':'POST',body:d,signal});await refreshSession();await mount({container,signal});});};
 button(container,'Thêm tài khoản',()=>edit());table(container,accounts,[['tai_khoan_id','ID'],['email','Email'],['mssv','MSSV'],['ho_ten','Họ tên'],['vai_tro','Vai trò'],['trang_thai','Trạng thái']],[['Sửa',edit]]);
 heading(container,'Nhân viên');const emp=document.createElement('section');container.append(emp);
 const editEmployee=(row={})=>{emp.replaceChildren();form(emp,[select('tai_khoan_id','Tài khoản',accounts.filter(a=>a.vai_tro!=='user').map(a=>[a.tai_khoan_id,a.email])),field('ma_nhan_vien','Mã nhân viên'),field('ho_ten','Họ tên'),field('dien_thoai','Điện thoại','tel',false),field('chuc_vu','Chức vụ','text',false),select('trang_thai','Trạng thái',[['active','Hoạt động'],['inactive','Ngừng hoạt động']])],row,async d=>{await request('/employees',{method:'POST',body:d,signal});await mount({container,signal});});};
 button(container,'Thêm nhân viên',()=>editEmployee());table(container,employees,[['ma_nhan_vien','Mã'],['email','Email'],['ho_ten','Họ tên'],['trang_thai','Trạng thái']],[['Sửa',editEmployee]]);
}
