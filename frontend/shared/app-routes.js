import {mount as books} from '../books/books.js';
import {mount as readers} from '../readers/live.js';
import {mount as fines} from '../fines/preview.js';
import {mount as accounts} from '../accounts/live.js';
import {mount as profile} from '../profile/live.js';
import {mount as loans} from '../loans/live.js';
import {mount as ebooks} from '../ebooks/live.js';
import {mount as bookshelf} from '../bookshelf/live.js';
import {mount as inventory} from '../inventory/live.js';
import {mount as reports} from '../reports/live.js';
import {mount as notifications} from '../notifications/live.js';
import {mount as ebookReader} from '../ebooks/reader.js';
export function routesFor(role){
 const operator=['librarian','admin'];
 return [
 {path:'/favorites',title:'Tủ sách yêu thích',mount:options=>bookshelf({...options,favoritesOnly:true})},
 {path:'/books',title:role==='user'?'Tra cứu sách':'Sách / kho',mount:books},
 {path:'/profile',title:'Hồ sơ của tôi',hiddenNavigation:true,mount:profile},
 {path:'/loans',title:'Mượn / trả sách',mount:loans},
 {path:'/ebooks',title:'Sách điện tử',mount:ebooks},
 {path:'/reader/:id',title:'Trình đọc eBook',hiddenNavigation:true,mount:ebookReader},
 {path:'/bookshelf',title:'Tủ sách / tiến độ',mount:bookshelf},
 {path:'/notifications',title:'Thông báo',hiddenNavigation:true,mount:notifications},
 {path:'/inventory',title:'Kiểm kê',roles:operator,mount:inventory},
 {path:'/reports',title:role==='admin'?'Báo cáo / chính sách':'Báo cáo',roles:operator,mount:reports},
 {path:'/readers',title:'Độc giả / thẻ',roles:operator,mount:readers},
 {path:'/fines',title:role==='user'?'Vi phạm của tôi':'Quản lý vi phạm',mount:fines},
 {path:'/accounts',title:'Tài khoản / nhân viên',roles:['admin'],mount:accounts}
 ];
}
