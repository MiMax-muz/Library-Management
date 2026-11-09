// Dữ liệu chỉ dùng để duyệt giao diện TV4 tuần 4; không phản ánh thông báo từ DB.
export const demoAccounts = Object.freeze([
  { id: 1, name: 'Nguyễn Minh Anh', code: 'SV001' },
  { id: 2, name: 'Trần Gia Bảo', code: 'SV002' },
]);

export const demoNotifications = Object.freeze([
  {
    id: 101, recipientId: 1, type: 'borrow-approved', readAt: null,
    createdAt: '2026-11-08T09:10:00+07:00',
    title: 'Yêu cầu mượn đã được duyệt',
    message: 'Yêu cầu mượn “Nhập môn lập trình” đã được thủ thư duyệt. Hãy đến quầy để nhận sách.',
    reference: 'Yêu cầu YC-1042',
  },
  {
    id: 102, recipientId: 1, type: 'due-reminder', readAt: null,
    createdAt: '2026-11-08T09:00:00+07:00',
    title: 'Sắp đến hạn trả sách',
    message: '“Tuyển tập văn học” sẽ đến hạn trả vào ngày 10/11/2026. Vui lòng kiểm tra phiếu mượn của bạn.',
    reference: 'Phiếu PM-0086 · Hạn 10/11/2026',
  },
  {
    id: 103, recipientId: 1, type: 'renewal-approved', readAt: null,
    createdAt: '2026-11-07T15:24:00+07:00',
    title: 'Gia hạn đã được duyệt',
    message: 'Sách “Cơ sở dữ liệu” đã được gia hạn. Hạn trả mới là ngày 21/11/2026.',
    reference: 'Phiếu PM-0079 · Hạn mới 21/11/2026',
  },
  {
    id: 104, recipientId: 1, type: 'borrow-rejected', readAt: '2026-11-06T11:00:00+07:00',
    createdAt: '2026-11-06T10:35:00+07:00',
    title: 'Yêu cầu mượn chưa được duyệt',
    message: 'Yêu cầu mượn “Thiết kế giao diện” không được duyệt. Bạn có thể xem lại tình trạng sách trước khi gửi yêu cầu mới.',
    reference: 'Yêu cầu YC-1031',
  },
  {
    id: 105, recipientId: 1, type: 'renewal-rejected', readAt: '2026-11-04T16:00:00+07:00',
    createdAt: '2026-11-04T14:20:00+07:00',
    title: 'Yêu cầu gia hạn chưa được duyệt',
    message: 'Yêu cầu gia hạn “Mạng máy tính” không được duyệt. Hạn trả hiện tại vẫn được giữ nguyên.',
    reference: 'Phiếu PM-0068',
  },
  {
    id: 201, recipientId: 2, type: 'borrow-approved', readAt: null,
    createdAt: '2026-11-08T08:40:00+07:00',
    title: 'Yêu cầu mượn đã được duyệt',
    message: 'Yêu cầu mượn “Toán rời rạc” đã được thủ thư duyệt. Hãy đến quầy để nhận sách.',
    reference: 'Yêu cầu YC-1050',
  },
  {
    id: 202, recipientId: 2, type: 'due-reminder', readAt: '2026-11-05T10:00:00+07:00',
    createdAt: '2026-11-05T09:00:00+07:00',
    title: 'Sắp đến hạn trả sách',
    message: '“Nhập môn kinh tế” sẽ đến hạn trả vào ngày 07/11/2026.',
    reference: 'Phiếu PM-0091 · Hạn 07/11/2026',
  },
]);
