import {data,table,heading,button} from '../shared/forms.js';

const money=n=>Number(n).toLocaleString('vi-VN')+' đ';
export async function paymentView(host,rows,signal){
 const summary=document.createElement('section'), history=document.createElement('section'), content=document.createElement('section');
 const notice=document.createElement('p');
 notice.setAttribute('role','status');
 const ledger=document.createElement('div');ledger.className='fine-ledger';ledger.append(summary,notice,content,history);host.append(ledger);
 let selected=null,timer;
 function draw(){
  summary.replaceChildren();content.replaceChildren();history.replaceChildren();
  const due=rows.filter(r=>!r.payment_id&&Number(r.total)>0),paid=rows.filter(r=>r.payment_id);
  const sum=(items,key)=>items.reduce((n,r)=>n+Number(r[key]),0);
  heading(summary,'Tình hình thanh toán');
  table(summary,[{total:money(sum(rows,'total')),paid:money(sum(paid,'paid_amount')),due:money(sum(due,'total'))}],[['total','Tổng tiền phạt'],['paid','Đã thanh toán'],['due','Còn phải nộp']]);

   heading(history,'Lịch sử thanh toán');
   table(history,paid.map(r=>({...r,date:new Date(r.paid_at.replace(' ','T')+'Z').toLocaleString('vi-VN',{timeZone:'Asia/Ho_Chi_Minh'}),amount:money(r.paid_amount),via:r.method==='transfer'?'Chuyển khoản':'Tiền mặt'})),[['date','Ngày thanh toán'],['ten_sach','Tên sách'],['amount','Số tiền'],['via','Hình thức thanh toán']]);
  heading(content,'Các sách cần thanh toán tiền phạt');
  table(content,due.map(r=>({...r,amount:money(r.total),status:'Chưa thanh toán'})),[['ma_ban_sach','Mã bản'],['ten_sach','Tên sách'],['amount','Tiền phạt'],['status','Trạng thái thanh toán']]);
  const total=document.createElement('p');total.className='fine-total';
  total.textContent=due.length+' khoản cần thanh toán · Tổng cộng '+money(sum(due,'total'));content.append(total);  content.querySelectorAll('tbody tr').forEach((tr,i)=>{if(due[i])button(tr.lastElementChild,'Mở mã QR',()=>openQR(due[i]));});
 }
 let version=0;
 function cancel(){version++;clearTimeout(timer);selected=null;}
 async function openQR(row){
  cancel();const active=version;selected=row.fine_id;draw();notice.textContent='';
  try{
   const cfg=await data('/fines/receiving-account',signal);
   if(signal.aborted||version!==active)return;
   if(!/^\d{6}$/.test(cfg.bank)||!/^\d{4,25}$/.test(cfg.account)){notice.textContent='Thư viện chưa cấu hình tài khoản nhận chuyển khoản.';return;}
   const panel=document.createElement('section');panel.className='panel';content.append(panel);
   const memo='UNILIB PHAT '+row.fine_id;
   heading(panel,'Chuyển khoản '+money(row.total));
   const image=document.createElement('img');image.width=240;image.alt='Mã QR thanh toán khoản phạt '+row.fine_id;
   image.src='https://img.vietqr.io/image/'+cfg.bank+'-'+cfg.account+'-compact2.png?'+new URLSearchParams({amount:row.total,addInfo:memo});
   image.onerror=()=>{if(version===active)notice.textContent='Không tải được mã QR. Vui lòng thử mở lại.';};
   const note=document.createElement('p');note.textContent='Nội dung chuyển khoản: '+memo+'. Thanh toán được ghi nhận sau khi thư viện xác nhận đã nhận tiền.';
   panel.append(image,note);panel.scrollIntoView({block:'nearest'});
   async function check(){
    try{
     const updated=await data('/fines',signal);
     if(signal.aborted||version!==active)return;
     const receipt=updated.find(r=>r.fine_id===selected&&r.payment_id);
     if(receipt){rows=updated;cancel();draw();notice.textContent='Thanh toán thành công · '+receipt.ten_sach+' · '+money(receipt.paid_amount);return;}
    }catch{if(signal.aborted)return;}
    if(version===active)timer=setTimeout(check,5000);
   }
   timer=setTimeout(check,5000);
  }catch(e){if(!signal.aborted&&version===active)notice.textContent=e.message;}
 }
 signal.addEventListener('abort',cancel,{once:true});draw();
}
