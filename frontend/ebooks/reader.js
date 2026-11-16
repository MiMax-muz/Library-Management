import {request} from '../shared/api.js';
import {data,field,form,table,heading} from '../shared/forms.js';
import {paragraph} from '../shared/actions.js';

export async function mount({container,signal,params}) {
  const fileId = encodeURIComponent(params.id);
  const state = await data('/reading/'+fileId, signal);
  if (signal.aborted) return;
  container.replaceChildren();
  heading(container, 'Trình đọc sách điện tử');
  paragraph(container, 'Tiến độ và dấu trang được lưu riêng theo tài khoản và phiên bản PDF. Dùng ô trang để lưu/khôi phục vị trí đọc.');
  const viewer = document.createElement('iframe');
  viewer.title = 'Nội dung PDF';
  viewer.className = 'ebook-reader-frame';
  viewer.loading = 'lazy';
  viewer.style.width = '100%';
  viewer.style.height = 'min(78vh, 1050px)';
  viewer.style.border = '1px solid #dceae3';
  viewer.style.borderRadius = '12px';
  const currentPage = Math.max(1, Number(state.progress?.page_number) || 1);
  viewer.src = '/api/ebook-files/'+fileId+'/content#page='+currentPage;
  container.append(viewer);

  const progressPanel = document.createElement('section');
  const progressInput = document.createElement('input');
  progressInput.type = 'number'; progressInput.min = '0'; progressInput.max = '100'; progressInput.step = '0.01';
  const pageInput = document.createElement('input');
  pageInput.type = 'number'; pageInput.min = '1'; pageInput.value = String(currentPage);
  const status = document.createElement('p'); status.setAttribute('role','status');
  const save = document.createElement('button'); save.className = 'btn'; save.type = 'button'; save.textContent = 'Lưu vị trí đọc';
  const controls = document.createElement('div'); controls.className = 'reader-progress-controls';
  controls.style.display = 'flex'; controls.style.flexWrap = 'wrap'; controls.style.alignItems = 'end'; controls.style.gap = '12px'; controls.style.padding = '16px 0';
  const pageLabel = document.createElement('label'); pageLabel.textContent = 'Trang hiện tại'; pageInput.className='input'; pageLabel.append(pageInput);
  const percentLabel = document.createElement('label'); percentLabel.textContent = 'Tiến độ (%)'; progressInput.className='input'; percentLabel.append(progressInput);
  controls.append(pageLabel, percentLabel, save, status); progressPanel.append(controls); container.append(progressPanel);
  progressInput.value = String(Number(state.progress?.percent) || 0);
  pageInput.addEventListener('change', () => {
    const page = Math.max(1, Math.floor(Number(pageInput.value) || 1));
    pageInput.value = String(page); viewer.src = '/api/ebook-files/'+fileId+'/content#page='+page;
  }, {signal});
  save.addEventListener('click', async () => {
    save.disabled = true;
    try {
      const page = Math.max(1, Math.floor(Number(pageInput.value) || 1));
      const percent = Number(progressInput.value);
      const result = await request('/reading/'+fileId, {method:'PUT', body:{page_number:page,percent}, signal});
      if (signal.aborted) return;
      viewer.src = '/api/ebook-files/'+fileId+'/content#page='+page;
      status.textContent = result.data?.saved ? 'Đã lưu tiến độ đọc.' : 'Không lưu được tiến độ.';
    } catch (error) { status.textContent = error.message; }
    finally { save.disabled = false; }
  }, {signal});

  const bookmarks = document.createElement('section'); heading(bookmarks, 'Dấu trang');
  form(bookmarks, [field('page_number','Trang đánh dấu','number'), field('note','Ghi chú')], {page_number:currentPage}, async values => {
    await request('/reading/'+fileId+'/bookmarks',{method:'POST',body:values,signal}); await refreshBookmarks();
  }, 'Thêm dấu trang');
  const bookmarkList = document.createElement('div'); bookmarks.append(bookmarkList); container.append(bookmarks);
  async function refreshBookmarks() {
    const refreshed = await data('/reading/'+fileId, signal); if (signal.aborted) return;
    bookmarkList.replaceChildren();
    table(bookmarkList, refreshed.bookmarks, [['page_number','Trang'],['note','Ghi chú']], [['Mở trang', row => { pageInput.value=String(row.page_number); pageInput.dispatchEvent(new Event('change')); }], ['Xóa', async row => { await request('/bookmarks/'+row.bookmark_id,{method:'DELETE',signal}); await refreshBookmarks(); }]]);
  }
  await refreshBookmarks();
}
