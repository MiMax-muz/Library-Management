// TV1: hiển thị trạng thái bằng textContent để tránh chèn HTML từ dữ liệu.
export function showState(container, message, kind = 'empty') {
  const element = document.createElement('p');
  element.className = kind;
  element.setAttribute('role', kind === 'status-error' ? 'alert' : 'status');
  element.textContent = message;
  container.replaceChildren(element);
}
export function toast(message) {
  const host = document.getElementById('app-notifications');
  const element = document.createElement('div');
  element.className = 'toast'; element.textContent = message;
  host.replaceChildren(element);
  setTimeout(() => element.remove(), 3500);
}
export function openDialog(title, content) {
  const previous = document.activeElement;
  const dialog = document.createElement('dialog'); dialog.className = 'modal';
  const heading = document.createElement('h3'); heading.textContent = title;
  const close = document.createElement('button'); close.className = 'btn outline'; close.textContent = 'Đóng';
  close.addEventListener('click', () => dialog.close());
  dialog.append(heading, content, close); document.body.append(dialog);
  dialog.addEventListener('close', () => { dialog.remove(); previous?.focus(); }, { once: true });
  dialog.showModal(); return () => dialog.close();
}
