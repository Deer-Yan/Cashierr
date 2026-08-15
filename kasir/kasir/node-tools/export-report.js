/**
 * Kasir Toko - Laporan Penjualan CLI (Node.js)
 * Fitur tambahan: export laporan penjualan harian ke file CSV tanpa membuka browser.
 *
 * Cara pakai:
 *   cd node-tools
 *   npm install
 *   node export-report.js --email=toko@email.com --tanggal=2026-08-12
 *
 * Jika --tanggal tidak diisi, akan menggunakan tanggal hari ini.
 */

const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

const DB_CONFIG = {
  host: 'localhost',
  user: 'root',
  password: '',
  database: 'kasir_toko',
};

function parseArgs() {
  const args = {};
  process.argv.slice(2).forEach((arg) => {
    const [key, value] = arg.replace(/^--/, '').split('=');
    args[key] = value;
  });
  return args;
}

function todayDate() {
  const d = new Date();
  return d.toISOString().slice(0, 10);
}

async function main() {
  const args = parseArgs();
  const email = args.email;
  const tanggal = args.tanggal || todayDate();

  if (!email) {
    console.error('Error: Wajib isi --email=<email_akun_toko>');
    process.exit(1);
  }

  console.log(`Membuat laporan penjualan untuk ${email} pada tanggal ${tanggal}...`);

  const conn = await mysql.createConnection(DB_CONFIG);

  try {
    const [users] = await conn.execute('SELECT id, nama_toko FROM users WHERE email = ?', [email]);
    if (users.length === 0) {
      console.error('Error: Akun dengan email tersebut tidak ditemukan.');
      process.exit(1);
    }
    const user = users[0];

    const [rows] = await conn.execute(
      `SELECT p.id AS penjualan_id, p.created_at, pd.nama_barang, pd.harga_jual, pd.qty, pd.subtotal,
              p.total_harga, p.uang_dibayar, p.kembalian
       FROM penjualan p
       JOIN penjualan_detail pd ON pd.penjualan_id = p.id
       WHERE p.user_id = ? AND DATE(p.created_at) = ?
       ORDER BY p.created_at ASC`,
      [user.id, tanggal]
    );

    if (rows.length === 0) {
      console.log('ℹ️  Tidak ada transaksi pada tanggal tersebut.');
      process.exit(0);
    }

    let csv = 'ID Transaksi,Tanggal,Barang,Harga Jual,Qty,Subtotal,Total Transaksi,Uang Dibayar,Kembalian\n';
    let totalOmzet = 0;
    const seenTransaksi = new Set();

    rows.forEach((r) => {
      csv += `${r.penjualan_id},"${r.created_at}","${r.nama_barang}",${r.harga_jual},${r.qty},${r.subtotal},${r.total_harga},${r.uang_dibayar},${r.kembalian}\n`;
      if (!seenTransaksi.has(r.penjualan_id)) {
        seenTransaksi.add(r.penjualan_id);
        totalOmzet += Number(r.total_harga);
      }
    });

    const outputDir = path.join(__dirname, 'reports');
    if (!fs.existsSync(outputDir)) fs.mkdirSync(outputDir);
    const fileName = `laporan_${user.nama_toko.replace(/\s+/g, '_')}_${tanggal}.csv`;
    const filePath = path.join(outputDir, fileName);
    fs.writeFileSync(filePath, csv);

    console.log(`Laporan berhasil dibuat: ${filePath}`);
    console.log(`   Jumlah transaksi : ${seenTransaksi.size}`);
    console.log(`   Total omzet      : Rp ${totalOmzet.toLocaleString('id-ID')}`);
  } finally {
    await conn.end();
  }
}

main().catch((err) => {
  console.error('Error: Terjadi kesalahan:', err.message);
  process.exit(1);
});
