// UC-TV42. Frontend demo data is persisted through the shared localStorage adapter.
const demoEmployees = [
  { code: 'NV001', name: 'Nguyễn Minh Anh', email: 'minhanh@unilib.edu.vn', phone: '0912345678', position: 'Thủ thư', status: 'Đang làm việc' },
  { code: 'NV002', name: 'Trần Quốc Bảo', email: 'quocbao@unilib.edu.vn', phone: '0987654321', position: 'Nhân viên thư viện', status: 'Đang làm việc' }
];

const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
const employeesOf = data => Array.isArray(data.employees) ? data.employees : demoEmployees;
const normalizedPhone = value => String(value).replace(/\D/g, '').replace(/^84/, '0');

export function renderEmployees(data) {
  const employees = employeesOf(data);
  const active = employees.filter(employee => employee.status === 'Đang làm việc').length;
  const rows = employees.map(employee => `<tr>
    <td><strong>${escapeHtml(employee.code)}</strong></td>
    <td>${escapeHtml(employee.name)}</td>
    <td>${escapeHtml(employee.email)}</td>
    <td>${escapeHtml(employee.phone)}</td>
    <td>${escapeHtml(employee.position)}</td>
    <td><span class="pill ${employee.status === 'Ngừng hoạt động' ? 'bad' : ''}">${escapeHtml(employee.status)}</span></td>
    <td><div class="employee-actions"><button class="btn outline" type="button" data-action="employee-edit" data-id="${escapeHtml(employee.code)}">Sửa</button>${employee.status === 'Đang làm việc' ? `<button class="btn danger" type="button" data-action="employee-stop" data-id="${escapeHtml(employee.code)}">Ngừng hoạt động</button>` : ''}</div></td>
  </tr>`).join('');
  return `<div class="employee-summary"><span>Tổng nhân viên: <strong>${employees.length}</strong></span><span>Đang làm việc: <strong>${active}</strong></span><span>Ngừng hoạt động: <strong>${employees.length - active}</strong></span></div>
    <section class="panel employee-panel"><div class="panel-head"><h3>Danh sách nhân viên</h3><button class="btn" type="button" data-action="employee-add">+ Thêm nhân viên</button></div>
    <div class="table-wrap"><table><thead><tr><th>Mã nhân viên</th><th>Họ tên</th><th>Email</th><th>Số điện thoại</th><th>Chức vụ</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>${rows || '<tr><td colspan="7" class="empty">Chưa có nhân viên. Hãy thêm nhân viên mới.</td></tr>'}</tbody></table></div></section>`;
}

function employeeForm(employee) {
  const editing = Boolean(employee);
  const value = key => escapeHtml(employee?.[key] ?? '');
  return `<form id="employee-form" data-original-code="${value('code')}" novalidate>
    <p id="employee-error" class="employee-error" role="alert" hidden></p>
    <label class="field">Mã nhân viên <input class="input" name="code" value="${value('code')}" maxlength="20" required placeholder="Ví dụ: NV003"></label>
    <label class="field">Họ tên <input class="input" name="name" value="${value('name')}" maxlength="100" required></label>
    <label class="field">Email <input class="input" name="email" type="email" value="${value('email')}" maxlength="150" required></label>
    <label class="field">Số điện thoại <input class="input" name="phone" type="tel" value="${value('phone')}" maxlength="20" required placeholder="0xxxxxxxxx hoặc +84xxxxxxxxx"></label>
    <label class="field">Chức vụ <input class="input" name="position" value="${value('position')}" maxlength="80" required></label>
    <label class="field">Trạng thái làm việc <select class="select" name="status"><option value="Đang làm việc" ${!editing || employee.status === 'Đang làm việc' ? 'selected' : ''}>Đang làm việc</option><option value="Ngừng hoạt động" ${employee?.status === 'Ngừng hoạt động' ? 'selected' : ''}>Ngừng hoạt động</option></select></label>
    <button class="btn" type="submit">Lưu</button>
  </form>`;
}

function validationError(employee, employees, originalCode) {
  if (!/^[A-Za-z0-9_-]{3,20}$/.test(employee.code)) return 'Mã nhân viên phải có 3–20 ký tự, chỉ gồm chữ không dấu, số, dấu gạch ngang hoặc gạch dưới.';
  if (employee.name.length < 2 || employee.name.length > 100) return 'Họ tên phải có từ 2 đến 100 ký tự.';
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(employee.email)) return 'Email không đúng định dạng.';
  if (!/^(?:0\d{9}|\+84\d{9})$/.test(employee.phone)) return 'Số điện thoại phải có dạng 0xxxxxxxxx hoặc +84xxxxxxxxx.';
  if (employee.position.length < 2 || employee.position.length > 80) return 'Chức vụ phải có từ 2 đến 80 ký tự.';
  const others = employees.filter(item => item.code !== originalCode);
  if (others.some(item => item.code.toLowerCase() === employee.code.toLowerCase())) return 'Mã nhân viên đã tồn tại.';
  if (others.some(item => item.email.toLowerCase() === employee.email.toLowerCase())) return 'Email đã được sử dụng.';
  if (others.some(item => normalizedPhone(item.phone) === normalizedPhone(employee.phone))) return 'Số điện thoại đã được sử dụng.';
  return '';
}

export function initEmployees({ db, toast, modal, render }) {
  document.addEventListener('click', event => {
    const button = event.target.closest('[data-action^="employee-"]');
    if (!button) return;
    const action = button.dataset.action;
    const employee = employeesOf(db.read()).find(item => item.code === button.dataset.id);
    if (action === 'employee-add') {
      modal('Thêm nhân viên', employeeForm(null));
    } else if (action === 'employee-edit' && employee) {
      modal('Cập nhật thông tin nhân viên', employeeForm(employee));
    } else if (action === 'employee-stop' && employee) {
      if (!window.confirm(`Xác nhận ngừng hoạt động nhân viên ${employee.name} (${employee.code})?`)) return;
      const data = db.read();
      const employees = employeesOf(data).map(item => ({ ...item }));
      const target = employees.find(item => item.code === employee.code);
      if (!target || target.status === 'Ngừng hoạt động') return;
      target.status = 'Ngừng hoạt động';
      try {
        db.write({ ...data, employees });
        render();
        toast('Cập nhật thông tin nhân viên thành công');
      } catch {
        toast('Cập nhật thất bại. Dữ liệu cũ được giữ nguyên.');
      }
    }
  });

  document.addEventListener('submit', event => {
    if (event.target.id !== 'employee-form') return;
    event.preventDefault();
    const form = event.target;
    const values = new FormData(form);
    const employee = Object.fromEntries(['code', 'name', 'email', 'phone', 'position', 'status'].map(key => [key, String(values.get(key) ?? '').trim()]));
    const originalCode = form.dataset.originalCode;
    const data = db.read();
    const employees = employeesOf(data).map(item => ({ ...item }));
    const error = validationError(employee, employees, originalCode);
    const errorBox = form.querySelector('#employee-error');
    if (error) {
      errorBox.textContent = error;
      errorBox.hidden = false;
      return;
    }
    if (originalCode) {
      const index = employees.findIndex(item => item.code === originalCode);
      if (index < 0) {
        errorBox.textContent = 'Không tìm thấy nhân viên cần cập nhật.';
        errorBox.hidden = false;
        return;
      }
      employees[index] = employee;
    } else {
      employees.push(employee);
    }
    try {
      db.write({ ...data, employees });
      form.closest('.modal-backdrop')?.remove();
      render();
      toast('Cập nhật thông tin nhân viên thành công');
    } catch {
      errorBox.textContent = 'Cập nhật thất bại. Dữ liệu cũ được giữ nguyên.';
      errorBox.hidden = false;
    }
  });
}
