import {mount as books} from '../books/books.js';
import {mount as readers} from '../readers/live.js';
import {mount as fines} from '../fines/preview.js';
import {mount as accounts} from '../accounts/live.js';
import {mount as profile,changePassword} from '../profile/live.js';
import {mount as loans} from '../loans/live.js';
import {mount as ebooks} from '../ebooks/live.js';
import {mount as bookHistory} from '../bookshelf/history.js';
import {mount as bookshelf} from '../bookshelf/personal.js';
import {mount as inventory} from '../inventory/live.js';
import {mount as reports} from '../reports/live.js';
import {mount as notifications} from '../notifications/live.js';
import {mount as ebookReader} from '../ebooks/reader.js';
export function routesFor(role){
 const operator=['librarian','admin'];
 return [
 {path:'/favorites',title:'Tủ sách cá nhân',mount:bookshelf},
 {path:'/books',title:role==='user'?'Tra cứu sách':'Sách / kho',mount:books},
 {path:'/books/:id',title:'Chi tiết sách',hiddenNavigation:true,mount:books},
 {path:'/change-password',title:'Đổi mật khẩu',hiddenNavigation:true,mount:changePassword},
 {path:'/profile',title:'Hồ sơ của tôi',hiddenNavigation:true,mount:profile},
 {path:'/loans/requests/:id',title:'Chi tiết phiếu mượn',hiddenNavigation:true,mount:loans},
 {path:'/loans',title:role==='user'?'Mượn sách':'Mượn / trả sách',mount:loans},
 {path:'/ebooks',title:role==='user'?'Sách điện tử':'Quản lý eBook',hiddenNavigation:role==='user',mount:ebooks},
 {path:'/reader/:id',title:'Trình đọc eBook',hiddenNavigation:true,mount:ebookReader},
 {path:'/bookshelf',title:'Lịch sử sách',mount:bookHistory},
 {path:'/notifications',title:'Thông báo',hiddenNavigation:true,mount:notifications},
 {path:'/inventory',title:'Kiểm kê',roles:operator,mount:inventory},
 {path:'/reports',title:role==='admin'?'Báo cáo / chính sách':'Báo cáo',roles:operator,mount:reports},
 {path:'/readers',title:'Độc giả / thẻ',roles:operator,mount:readers},
 {path:'/fines',title:role==='user'?'Vi phạm của tôi':'Quản lý vi phạm',mount:fines},
 {path:'/accounts',title:'Tài khoản / nhân viên',roles:['admin'],mount:accounts}
 ];
}
