import {borrowCatalog} from './catalog.js';
import {request} from '../shared/api.js';
import {currentUser} from '../shared/session.js';
import {data,form,field,select,heading,table} from '../shared/forms.js';
import {action,paragraph,states} from '../shared/actions.js';
export async function mount({container,signal,params={}}){
 const staff=currentUser().vai_tro!=='user';const [requests,items,books]=await Promise.all([data('/loans/requests',signal),(staff||params.id)?data('/loans/items',signal):[],(staff||params.id)?Promise.resolve([]):data('/books',signal)]);if(signal.aborted)return;
 container.replaceChildren();const refresh=()=>mount({container,signal,params});const send=async(path,body={})=>{await request(path,{method:'POST',body,signal});await refresh();};
 if(params.id){
  const r=requests.find(row=>String(row.request_id)===params.id);
  if(!r){paragraph(container,'Không tìm thấy phiếu mượn.');return;}
  const detail=container;
  const localTime=value=>new Date(value.replace(' ','T')+'Z').toLocaleString('vi-VN',{timeZone:'Asia/Ho_Chi_Minh'});
  heading(detail,'Thông tin phiếu mượn');
  paragraph(detail,'Mã phiếu: '+r.request_id+' · '+r.ho_ten+' · '+states[r.status]);
  paragraph(detail,'Ngày giờ gửi phiếu: '+localTime(r.requested_at));
  const borrowed=new Map(items.filter(i=>String(i.request_id)===params.id).map(i=>[String(i.ban_sach_id),i]));
  const rows=r.copies.map(copy=>{
   const item=borrowed.get(String(copy.ban_sach_id));
   return {...copy,borrowed:item?localTime(item.handed_over_at):'Chưa bàn giao',due:item?item.due_date.split('-').reverse().join('/'):'Chưa có hạn trả'};
  });
  table(detail,rows,[['ten_sach','Tên sách'],['ma_ban_sach','Mã bản'],['borrowed','Ngày giờ mượn'],['due','Hạn trả']]);
  if(!staff){
   for(const item of borrowed.values()){
    if(item.status!=='borrowed')continue;
    if(item.pending_renewal)paragraph(detail,item.ma_ban_sach+' · Đang chờ duyệt gia hạn');
    else action(detail,'Yêu cầu gia hạn · '+item.ma_ban_sach,()=>send('/loans/items/'+item.loan_item_id+'/renew'));
   }
  }  if(staff&&r.status==='pending'){action(detail,'Duyệt yêu cầu',()=>send('/loans/requests/'+r.request_id+'/approve'));action(detail,'Từ chối yêu cầu',()=>send('/loans/requests/'+r.request_id+'/reject'));}if(currentUser().vai_tro==='librarian' && r.status==='approved'){
   form(detail,[
    field('borrowed_at','Ngày giờ mượn (giờ Việt Nam)','datetime-local'),
    field('due_date','Hạn trả cho các sách trong phiếu','date')
   ],{},d=>send('/loans/requests/'+r.request_id+'/handover',d),'Xác nhận bàn giao');
  }if(['pending','approved'].includes(r.status))action(detail,'Hủy yêu cầu',()=>send('/loans/requests/'+r.request_id+'/cancel'));
  return;
 }
 if(!staff){const policy=await data('/loans/policy',signal);if(signal.aborted)return;borrowCatalog(container,books,policy,signal,refresh);}
 const requestsPanel=document.createElement('section');requestsPanel.className='loan-requests-panel';container.append(requestsPanel);heading(requestsPanel,'Yêu cầu mượn');
 table(requestsPanel,requests.map(r=>({...r,state:states[r.status],titles:r.copies.map(c=>c.ten_sach+' ('+c.ma_ban_sach+')').join(', ')})),[['request_id','Mã phiếu'],['ho_ten','Độc giả'],['titles','Sách'],['state','Trạng thái']],[['Xử lý / chi tiết',r=>window.open('#/loans/requests/'+r.request_id,'_blank','noopener')]]);
 if(!staff)return;
 heading(container,'Phiếu mượn / trả / gia hạn');const results=document.createElement('section'),editor=document.createElement('section');
 const draw=rows=>{results.replaceChildren();table(results,rows.map(i=>({...i,state:states[i.status]})),[['loan_item_id','Mã lượt'],['ho_ten','Độc giả'],['ten_sach','Sách'],['ma_ban_sach','Bản'],['due_date','Hạn trả'],['renewal_count','Lần gia hạn'],['state','Trạng thái'],['fine_total','Phạt đã ghi']],[['Chi tiết / thao tác',i=>{editor.replaceChildren();heading(editor,i.ten_sach+' · '+i.ma_ban_sach);paragraph(editor,'Hạn trả: '+i.due_date);if(i.status!=='borrowed'){paragraph(editor,'Lượt mượn đã kết thúc.');return;}
 if(i.pending_renewal){paragraph(editor,'Có yêu cầu gia hạn đang chờ duyệt.');if(staff){action(editor,'Duyệt gia hạn',()=>send('/loans/renewals/'+i.pending_renewal,{approve:true}));action(editor,'Từ chối gia hạn',()=>send('/loans/renewals/'+i.pending_renewal,{approve:false}));}}else action(editor,'Yêu cầu gia hạn',()=>send('/loans/items/'+i.loan_item_id+'/renew'));
 if(staff){paragraph(editor,'Xác nhận trả sẽ cập nhật kho và ghi khoản phạt theo chính sách hiện tại; chưa thu tiền.');form(editor,[select('violation','Tình trạng trả',[['overdue','Bình thường (tính quá hạn nếu có)'],['lost','Mất'],['damaged','Hỏng không phục hồi'],['repair','Hỏng có thể sửa']]),field('repair_cost','Chi phí sửa','number',false),field('repair_evidence','Căn cứ sửa chữa','text',false)],{},async d=>{if(confirm('Xác nhận trả và ghi nhận phạt cho bản sách này?'))await send('/loans/items/'+i.loan_item_id+'/return',d);},'Xác nhận trả / phạt');}
 }]]);};
 if(staff) form(container,[select('status','Trạng thái',[['all','Tất cả'],['borrowed','Đang mượn'],['overdue','Quá hạn'],['returned','Đã trả'],['lost','Mất'],['damaged','Hỏng']]),field('from','Ngày bàn giao từ','date',false),field('to','Đến ngày','date',false)],{},async d=>draw(await data('/loans/items?'+new URLSearchParams(d),signal)),'Lọc phiếu');container.append(results,editor);draw(items);
}
