// Xác thực mẫu cho B1. Backend phải tự xác thực lại khi được tích hợp.
const DEMO_PASSWORD = "123456";
const encoder = new TextEncoder();

const clean = (value) => String(value ?? "").trim();
const same = (a, b) =>
  clean(a).toLocaleLowerCase("vi") === clean(b).toLocaleLowerCase("vi");
const phoneKey = (value) => clean(value).replace(/\D/g, "").replace(/^84/, "0");

export function renderAuth(view = "login") {
  const registering = view === "register";
  const form = registering
    ? `<div class="eyebrow">TẠO TÀI KHOẢN SINH VIÊN</div>
       <h2>Đăng ký UniLib</h2>
       <p class="sub">Tạo tài khoản để sử dụng thư viện điện tử.</p>
       <form id="register-form" novalidate>
         <p class="auth-error" role="alert" hidden></p>
         <label class="field">Tên đăng nhập
           <input class="input" name="username" autocomplete="username" maxlength="24" required placeholder="4–24 ký tự, không dấu">
         </label>
         <label class="field">Họ tên
           <input class="input" name="name" autocomplete="name" maxlength="100" required>
         </label>
         <label class="field">Email
           <input class="input" type="email" name="email" autocomplete="email" maxlength="150" required>
         </label>
         <label class="field">Số điện thoại
           <input class="input" type="tel" name="phone" autocomplete="tel" maxlength="13" required placeholder="0xxxxxxxxx hoặc +84xxxxxxxxx">
         </label>
         <label class="field">Mật khẩu
           <input class="input" type="password" name="password" autocomplete="new-password" minlength="8" required placeholder="Ít nhất 8 ký tự">
         </label>
         <label class="field">Nhập lại mật khẩu
           <input class="input" type="password" name="confirmPassword" autocomplete="new-password" required>
         </label>
         <button class="btn auth-submit" type="submit">Tạo tài khoản</button>
       </form>
       <p class="auth-switch">Đã có tài khoản? <a href="#login" data-action="show-login">Đăng nhập</a></p>
       <p class="auth-note">Bản demo chỉ tạo tài khoản sinh viên; hồ sơ độc giả được bổ sung ở module riêng.</p>`
    : `<div class="eyebrow">WELCOME BACK</div>
       <h2>Đăng nhập UniLib</h2>
       <p class="sub">Truy cập không gian học tập của bạn</p>
       <form id="login-form" novalidate>
         <p class="auth-error" role="alert" hidden></p>
         <label class="field">Tên đăng nhập
           <input class="input" name="username" autocomplete="username" required placeholder="Tên đăng nhập của bạn">
         </label>
         <label class="field">Mật khẩu
           <input class="input" type="password" name="password" autocomplete="current-password" required placeholder="Nhập mật khẩu">
         </label>
         <button class="btn auth-submit" type="submit">Đăng nhập →</button>
       </form>
       <p class="auth-switch">Chưa có tài khoản sinh viên? <a href="#register" data-action="show-register">Đăng ký</a></p>
       <p class="demo">Tài khoản demo: <b>student, librarian, staff, admin</b><br>Mật khẩu: <b>123456</b><br>⚠️ Chỉ là đăng nhập mô phỏng trên trình duyệt.</p>`;

  return `<div class="login-wrap">
    <div class="login-art"><div>
      <div class="auth-logo">UniLib<span>.</span></div>
      <h1>Tri thức mở ra tương lai.</h1>
      <p>Thư viện số dành cho cộng đồng đại học hiện đại.</p>
      <div class="auth-book">📚</div>
    </div></div>
    <div class="login-form"><div class="login-card">${form}</div></div>
  </div>`;
}

export function validateRegistration(input, accounts) {
  const username = clean(input.username);
  const name = clean(input.name);
  const email = clean(input.email);
  const phone = clean(input.phone);
  const password = String(input.password ?? "");
  const confirmPassword = String(input.confirmPassword ?? "");

  if (!username || !name || !email || !phone || !password || !confirmPassword)
    return "Vui lòng nhập đầy đủ các trường bắt buộc.";
  if (!/^[A-Za-z0-9_.-]{4,24}$/.test(username))
    return "Tên đăng nhập phải có 4–24 ký tự, chỉ gồm chữ không dấu, số, dấu chấm, gạch ngang hoặc gạch dưới.";
  if (name.length < 2 || name.length > 100)
    return "Họ tên phải có từ 2 đến 100 ký tự.";
  if (email.length > 150 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
    return "Email không đúng định dạng.";
  if (!/^(?:0\d{9}|\+84\d{9})$/.test(phone))
    return "Số điện thoại phải có dạng 0xxxxxxxxx hoặc +84xxxxxxxxx.";
  if (password.length < 8) return "Mật khẩu phải có ít nhất 8 ký tự.";
  if (password !== confirmPassword) return "Mật khẩu nhập lại không khớp.";
  if (accounts.some((account) => same(account.id, username)))
    return "Tên đăng nhập đã tồn tại.";
  if (accounts.some((account) => account.email && same(account.email, email)))
    return "Email đã được sử dụng.";
  if (
    accounts.some(
      (account) => account.phone && phoneKey(account.phone) === phoneKey(phone),
    )
  )
    return "Số điện thoại đã được sử dụng.";
  return "";
}

async function passwordHash(salt, password) {
  const bytes = await crypto.subtle.digest(
    "SHA-256",
    encoder.encode(`${salt}:${password}`),
  );
  return Array.from(new Uint8Array(bytes), (byte) =>
    byte.toString(16).padStart(2, "0"),
  ).join("");
}

export async function createCredential(password) {
  if (!globalThis.crypto?.subtle || !globalThis.crypto.getRandomValues)
    throw new Error(
      "Trình duyệt không hỗ trợ tạo tài khoản demo. Hãy chạy ứng dụng qua Live Server trên localhost.",
    );
  const salt = Array.from(crypto.getRandomValues(new Uint8Array(16)), (byte) =>
    byte.toString(16).padStart(2, "0"),
  ).join("");
  return { salt, hash: await passwordHash(salt, password) };
}

export async function registerAccount(db, input) {
  const data = db.read();
  const accounts = Array.isArray(data.accounts) ? data.accounts : [];
  const error = validateRegistration(input, accounts);
  if (error) throw new Error(error);
  const password = String(input.password);
  const credential = await createCredential(password);
  const account = {
    id: clean(input.username),
    name: clean(input.name),
    email: clean(input.email),
    phone: clean(input.phone),
    role: "student",
    status: "Hoạt động",
    credential,
  };

  // Đọc lại ngay trước khi lưu để phát hiện tài khoản vừa được tạo ở tab khác.
  const latest = db.read();
  const currentAccounts = Array.isArray(latest.accounts) ? latest.accounts : [];
  const latestError = validateRegistration(input, currentAccounts);
  if (latestError) throw new Error(latestError);
  db.write({ ...latest, accounts: [...currentAccounts, account] });
  return account;
}

export async function authenticateAccount(db, username, password) {
  const accounts = db.read().accounts ?? [];
  const account = accounts.find((item) => same(item.id, username));
  if (!account || account.status !== "Hoạt động") return null;
  if (account.credential) {
    const hash = await passwordHash(
      account.credential.salt,
      String(password ?? ""),
    );
    return hash === account.credential.hash ? account : null;
  }
  // Bốn tài khoản mẫu ban đầu vẫn dùng mật khẩu chung trong bản demo.
  return ["student", "librarian", "staff", "admin"].includes(account.id) &&
    password === DEMO_PASSWORD
    ? account
    : null;
}
