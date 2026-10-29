import { ROLE_LABELS } from "../core/routes.js";
import { createCredential } from "./auth.js";

const ROLES = ["student", "librarian", "staff", "admin"];
const STATUSES = ["Hoạt động", "Khóa"];
const clean = (value) => String(value ?? "").trim();
const same = (a, b) =>
  clean(a).toLocaleLowerCase("vi") === clean(b).toLocaleLowerCase("vi");
const phoneKey = (value) => clean(value).replace(/\D/g, "").replace(/^84/, "0");
const escapeHtml = (value) =>
  String(value ?? "").replace(
    /[&<>"']/g,
    (char) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[
        char
      ],
  );
const accountsOf = (data) =>
  Array.isArray(data.accounts) ? data.accounts : [];

export function renderAccounts(data) {
  const accounts = accountsOf(data);
  const active = accounts.filter(
    (account) => account.status === "Hoạt động",
  ).length;
  const rows = accounts
    .map(
      (account) => `<tr>
    <td><strong>${escapeHtml(account.id)}</strong></td>
    <td>${escapeHtml(account.name)}</td>
    <td>${escapeHtml(account.email || "—")}</td>
    <td>${escapeHtml(account.phone || "—")}</td>
    <td>${escapeHtml(ROLE_LABELS[account.role] || account.role)}</td>
    <td><span class="pill ${account.status === "Hoạt động" ? "" : "bad"}">${escapeHtml(account.status)}</span></td>
    <td><button class="btn outline" type="button" data-action="account-edit" data-id="${escapeHtml(account.id)}">Sửa</button></td>
  </tr>`,
    )
    .join("");

  return `<div class="account-summary">
    <span>Tổng tài khoản: <strong>${accounts.length}</strong></span>
    <span>Hoạt động: <strong>${active}</strong></span>
    <span>Đã khóa: <strong>${accounts.length - active}</strong></span>
  </div>
  <section class="panel account-panel">
    <div class="panel-head"><h3>Danh sách tài khoản</h3><button class="btn" type="button" data-action="account-add">+ Thêm tài khoản</button></div>
    <div class="table-wrap"><table>
      <thead><tr><th>Tên đăng nhập</th><th>Họ tên</th><th>Email</th><th>Số điện thoại</th><th>Vai trò</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
      <tbody>${rows || '<tr><td colspan="7" class="empty">Chưa có tài khoản. Hãy thêm tài khoản mới.</td></tr>'}</tbody>
    </table></div>
  </section>`;
}

function accountForm(account) {
  const editing = Boolean(account);
  const value = (key) => escapeHtml(account?.[key] ?? "");
  const roleOptions = ROLES.map(
    (role) =>
      `<option value="${role}" ${account?.role === role ? "selected" : ""}>${escapeHtml(ROLE_LABELS[role])}</option>`,
  ).join("");
  const statusOptions = STATUSES.map(
    (status) =>
      `<option value="${status}" ${(!editing && status === "Hoạt động") || account?.status === status ? "selected" : ""}>${status}</option>`,
  ).join("");

  return `<form id="account-form" data-original-id="${value("id")}" novalidate>
    <p class="account-error" role="alert" hidden></p>
    <label class="field">Tên đăng nhập
      <input class="input" name="id" value="${value("id")}" maxlength="24" ${editing ? "readonly" : "required"} placeholder="4–24 ký tự, không dấu">
    </label>
    <label class="field">Họ tên
      <input class="input" name="name" value="${value("name")}" maxlength="100" required>
    </label>
    <label class="field">Email ${editing && !account.email ? "(có thể bổ sung sau)" : ""}
      <input class="input" type="email" name="email" value="${value("email")}" maxlength="150" ${!editing || account.email ? "required" : ""}>
    </label>
    <label class="field">Số điện thoại ${editing && !account.phone ? "(có thể bổ sung sau)" : ""}
      <input class="input" type="tel" name="phone" value="${value("phone")}" maxlength="13" ${!editing || account.phone ? "required" : ""} placeholder="0xxxxxxxxx hoặc +84xxxxxxxxx">
    </label>
    <label class="field">Vai trò
      <select class="select" name="role">${roleOptions}</select>
    </label>
    <label class="field">Trạng thái
      <select class="select" name="status">${statusOptions}</select>
    </label>
    <label class="field">Mật khẩu ${editing ? "(để trống nếu giữ nguyên)" : ""}
      <input class="input" type="password" name="password" autocomplete="new-password" ${editing ? "" : "required"} placeholder="Ít nhất 8 ký tự">
    </label>
    <label class="field">Nhập lại mật khẩu
      <input class="input" type="password" name="confirmPassword" autocomplete="new-password" ${editing ? "" : "required"}>
    </label>
    <button class="btn" type="submit">Lưu tài khoản</button>
  </form>`;
}

export function validateAccount(input, accounts, originalId = "") {
  const id = clean(input.id);
  const name = clean(input.name);
  const email = clean(input.email);
  const phone = clean(input.phone);
  const role = clean(input.role);
  const status = clean(input.status);
  const password = String(input.password ?? "");
  const confirmPassword = String(input.confirmPassword ?? "");
  const previous = accounts.find((account) => account.id === originalId);

  if (originalId && !previous) return "Không tìm thấy tài khoản cần cập nhật.";
  if (!id || !name || (!originalId && (!email || !phone || !password)))
    return "Vui lòng nhập đầy đủ các trường bắt buộc.";
  if (originalId && id !== originalId)
    return "Tên đăng nhập không thể thay đổi sau khi tạo.";
  if (!/^[A-Za-z0-9_.-]{4,24}$/.test(id))
    return "Tên đăng nhập phải có 4–24 ký tự, chỉ gồm chữ không dấu, số, dấu chấm, gạch ngang hoặc gạch dưới.";
  if (name.length < 2 || name.length > 100)
    return "Họ tên phải có từ 2 đến 100 ký tự.";
  if (
    (!email && previous?.email) ||
    (email && (email.length > 150 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)))
  )
    return "Email không đúng định dạng hoặc đang để trống.";
  if (
    (!phone && previous?.phone) ||
    (phone && !/^(?:0\d{9}|\+84\d{9})$/.test(phone))
  )
    return "Số điện thoại phải có dạng 0xxxxxxxxx hoặc +84xxxxxxxxx.";
  if (!ROLES.includes(role)) return "Vai trò không hợp lệ.";
  if (!STATUSES.includes(status)) return "Trạng thái không hợp lệ.";
  if (password && password.length < 8)
    return "Mật khẩu phải có ít nhất 8 ký tự.";
  if (password !== confirmPassword) return "Mật khẩu nhập lại không khớp.";

  const others = accounts.filter((account) => account.id !== originalId);
  if (others.some((account) => same(account.id, id)))
    return "Tên đăng nhập đã tồn tại.";
  if (
    email &&
    others.some((account) => account.email && same(account.email, email))
  )
    return "Email đã được sử dụng.";
  if (
    phone &&
    others.some(
      (account) => account.phone && phoneKey(account.phone) === phoneKey(phone),
    )
  )
    return "Số điện thoại đã được sử dụng.";
  return "";
}

export async function saveAccount(db, input, originalId = "") {
  const first = db.read();
  const firstError = validateAccount(input, accountsOf(first), originalId);
  if (firstError) throw new Error(firstError);

  const credential = input.password
    ? await createCredential(String(input.password))
    : undefined;
  const data = db.read();
  const accounts = accountsOf(data);
  const error = validateAccount(input, accounts, originalId);
  if (error) throw new Error(error);

  const previous = accounts.find((account) => account.id === originalId);
  const account = {
    ...(previous || {}),
    id: clean(input.id),
    name: clean(input.name),
    email: clean(input.email),
    phone: clean(input.phone),
    role: clean(input.role),
    status: clean(input.status),
    ...(credential ? { credential } : {}),
  };
  const updated = originalId
    ? accounts.map((item) => (item.id === originalId ? account : item))
    : [...accounts, account];
  try {
    db.write({ ...data, accounts: updated });
  } catch {
    throw new Error("Không thể lưu tài khoản. Dữ liệu cũ được giữ nguyên.");
  }
  return account;
}

export function initAccounts({ db, modal, render, toast }) {
  document.addEventListener("click", (event) => {
    const button = event.target.closest('[data-action^="account-"]');
    if (!button) return;
    if (button.dataset.action === "account-add") {
      modal("Thêm tài khoản", accountForm(null));
      return;
    }
    if (button.dataset.action === "account-edit") {
      const account = accountsOf(db.read()).find(
        (item) => item.id === button.dataset.id,
      );
      if (account)
        modal(
          `Cập nhật tài khoản: ${escapeHtml(account.id)}`,
          accountForm(account),
        );
    }
  });

  document.addEventListener("submit", async (event) => {
    if (event.target.id !== "account-form") return;
    event.preventDefault();
    const form = event.target;
    const originalId = form.dataset.originalId;
    const values = Object.fromEntries(new FormData(form));
    const errorBox = form.querySelector(".account-error");
    const submitButton = form.querySelector('[type="submit"]');
    errorBox.hidden = true;
    submitButton.disabled = true;
    try {
      const account = await saveAccount(db, values, originalId);
      form.closest(".modal-backdrop")?.remove();
      const currentId = localStorage.getItem("unilib_account_id") || "admin";
      if (
        originalId === currentId &&
        (account.role !== "admin" || account.status !== "Hoạt động")
      ) {
        localStorage.removeItem("unilib_role");
        localStorage.removeItem("unilib_account_id");
        location.href = "../../index.html";
        return;
      }
      render();
      toast("Đã lưu thông tin tài khoản.");
    } catch (error) {
      errorBox.textContent = error.message || "Cập nhật tài khoản thất bại.";
      errorBox.hidden = false;
      submitButton.disabled = false;
    }
  });
}
