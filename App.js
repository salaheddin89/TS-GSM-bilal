import React, { useState, useEffect } from 'react';
import axios from 'axios';
import './App.css';

const API = "https://yourdomain.com/api.php"; // استبدله برابط موقعك

function App() {
  const [user, setUser] = useState(JSON.parse(localStorage.getItem('user')));
  const [view, setView] = useState('repairs');
  const [repairs, setRepairs] = useState([]);

  useEffect(() => { if(user) fetchRepairs(); }, [user]);

  const fetchRepairs = async () => {
    const res = await axios.get(`${API}?action=get_repairs`);
    setRepairs(res.data);
  };

  const sendWhatsApp = (p) => {
    let phone = p.customer_phone.startsWith('0') ? p.customer_phone.substring(1) : p.customer_phone;
    let msg = `السلام عليكم ${p.customer_name}، جهازك ${p.device_model} راهو واجد.`;
    window.open(`https://wa.me/213${phone}?text=${encodeURIComponent(msg)}`, '_blank');
  };

  if (!user) return <Login onLogin={setUser} />;

  return (
    <div className="app-container">
      <div className="sidebar">
        <h2>لوحة المحترف</h2>
        <button onClick={() => setView('repairs')}>📦 أجهزة الصيانة</button>
        <button onClick={() => setView('add')}>➕ إستلام جديد</button>
        <button onClick={() => setView('search')}>🔍 بحث IMEI</button>
        <button onClick={() => { localStorage.clear(); setUser(null); }}>🚪 خروج</button>
      </div>

      <div className="content">
        {view === 'repairs' && (
          <div className="card">
            <h3>قائمة الأجهزة في المحل</h3>
            <table>
              <thead><tr><th>الزبون</th><th>الجهاز</th><th>الحالة</th><th>التواصل</th></tr></thead>
              <tbody>
                {repairs.map(r => (
                  <tr key={r.id}>
                    <td>{r.customer_name}</td>
                    <td>{r.device_brand} {r.device_model}</td>
                    <td>{r.status}</td>
                    <td>
                      <button className="btn-whatsapp" onClick={() => sendWhatsApp(r)}>واتساب</button>
                      <a href={`tel:${r.customer_phone}`}><button>اتصال</button></a>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        {/* أضف باقي الشاشات هنا بنفس الطريقة */}
      </div>
    </div>
  );
}

function Login({ onLogin }) {
  const [u, setU] = useState(''); const [p, setP] = useState('');
  const handle = async () => {
    const res = await axios.post(`${API}?action=login`, { username: u, password: p });
    if(res.data.status === 'success') {
      localStorage.setItem('user', JSON.stringify(res.data.user));
      onLogin(res.data.user);
    } else alert("خطأ في الدخول");
  };
  return (
    <div style={{ padding: '100px', textAlign: 'center' }}>
      <div className="card" style={{ maxWidth: '400px', margin: 'auto' }}>
        <h2>دخول النظام</h2>
        <input type="text" placeholder="المستخدم" onChange={e => setU(e.target.value)} />
        <input type="password" placeholder="كلمة السر" onChange={e => setP(e.target.value)} />
        <button onClick={handle}>دخول</button>
      </div>
    </div>
  );
}

export default App;