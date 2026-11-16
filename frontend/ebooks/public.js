import {data,table,heading} from '../shared/forms.js';
import {link} from '../shared/actions.js';
export async function mount({container,signal}){const rows=await data('/public/ebooks',signal);if(signal.aborted)return;container.replaceChildren();heading(container,'eBook công khai');link(container,'Về đăng nhập','#/login');table(container,rows,[['ten_sach','Sách'],['file_version','Phiên bản']],[['Đọc PDF',r=>window.open('/api/ebook-files/'+r.ebook_file_id+'/content','_blank','noopener')]]);}
