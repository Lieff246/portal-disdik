"""
Convert backbone_sekolah.sql (INSERT INTO `backbone_sekolah`)
→ sekolah_import.sql (INSERT INTO `sekolah` dengan kolom yang match)

Juga bersihkan:
- koordinat format aneh (misal -1461200000000) → decimal proper (-1.461200)
- tanggal '1900-01-01' → NULL
- string '-', '****', '******' → NULL
"""

import re
import sys

INPUT_FILE = "backbone_sekolah.sql"
OUTPUT_FILE = "sekolah_import.sql"

# Kolom dari INSERT di backbone_sekolah.sql (urutan tetap sama persis)
BACKBONE_COLS = [
    "kode_kecamatan","wilayah_bencana_sosial","sertifikasi_iso","akses_internet",
    "akreditasi","keaktifan","kode_desa_kelurahan","lintang","waktu_penyelenggaraan_id",
    "sumber_listrik","nomor_telepon","npsn","bujur","sertifikasi_iso_id","sekolah_id",
    "desa_kelurahan","alamat_jalan","kebutuhan_khusus","rekening_atas_nama","kecamatan",
    "last_update","flag","status_kepemilikan","akses_internet_2","akses_internet_id",
    "status_sekolah","sk_pendirian_sekolah","website","kontinuitas_listrik","email",
    "akses_internet_2_id","bentuk_pendidikan_id","status_kepemilikan_id",
    "tanggal_sk_izin_operasional","no_rekening","tanggal_sk_pendirian",
    "wilayah_adat_terpencil","cabang_kcp_unit","create_date","kode_provinsi",
    "status_sekolah_id","mbs","wilayah_perbatasan","yayasan_id","rw","nama_bank",
    "luas_tanah_bukan_milik","rt","yayasan","nama_nomenklatur","kode_kabupaten",
    "kode_registrasi","bentuk_pendidikan","wilayah_transmigrasi","partisipasi_bos",
    "soft_delete_sekolah","provinsi","wilayah_bencana_alam","waktu_penyelenggaraan",
    "kebutuhan_khusus_id","sk_izin_operasional","nomor_fax","nm_wp","kode_wilayah",
    "daya_listrik","wilayah_terpencil","kode_pos","nama_dusun","jarak_listrik",
    "nama","nss","luas_tanah_milik","npwp","sumber_listrik_id","kabupaten","semester_id"
]

# Kolom target di tabel sekolah (dari migration) — urutan output
TARGET_COLS = [
    "sekolah_id","semester_id","nama","nama_nomenklatur","nss","npsn",
    "bentuk_pendidikan_id","bentuk_pendidikan","alamat_jalan","rt","rw","nama_dusun",
    "kode_wilayah","kode_desa_kelurahan","desa_kelurahan","kode_kecamatan","kecamatan",
    "kode_kabupaten","kabupaten","kode_provinsi","provinsi","kode_pos","lintang","bujur",
    "nomor_telepon","nomor_fax","email","website","kebutuhan_khusus_id","kebutuhan_khusus",
    "status_sekolah_id","status_sekolah","sk_pendirian_sekolah","tanggal_sk_pendirian",
    "status_kepemilikan_id","status_kepemilikan","yayasan_id","yayasan",
    "sk_izin_operasional","tanggal_sk_izin_operasional","no_rekening","nama_bank",
    "cabang_kcp_unit","rekening_atas_nama","mbs","luas_tanah_milik","luas_tanah_bukan_milik",
    "kode_registrasi","npwp","nm_wp","keaktifan","flag","daya_listrik","kontinuitas_listrik",
    "jarak_listrik","wilayah_terpencil","wilayah_perbatasan","wilayah_transmigrasi",
    "wilayah_adat_terpencil","wilayah_bencana_alam","wilayah_bencana_sosial",
    "partisipasi_bos","waktu_penyelenggaraan_id","waktu_penyelenggaraan",
    "sumber_listrik_id","sumber_listrik","sertifikasi_iso_id","sertifikasi_iso",
    "akses_internet_id","akses_internet","akses_internet_2_id","akses_internet_2",
    "akreditasi","create_date","last_update","soft_delete_sekolah",
    # Kolom tambahan project — default values
    "jumlah_siswa","daya_tampung","is_3t","is_sekolah_alam"
]

COORD_COLS = {"lintang", "bujur"}
DATE_COLS  = {"tanggal_sk_pendirian","tanggal_sk_izin_operasional","create_date","last_update"}
BAD_DATES  = {"'1900-01-01 00:00:00'","'1910-01-01 00:00:00'","'0000-00-00 00:00:00'","'1900-01-01'"}
BAD_STRINGS = {"'-'","'****'","'******'","'http://'"}

def fix_koordinat(val: str, col: str) -> str:
    """
    Koordinat dari Excel kadang dalam format integer besar
    misal: '-1461200000000' → sebenarnya -1.46120 (dibagi 10^10)
    atau   '123471300000000' → 12.34713 (dibagi 10^10)
    """
    if val == "NULL":
        return "NULL"
    raw = val.strip("'")
    if raw in ("0", ""):
        return "NULL"
    try:
        f = float(raw)
        # Kalau nilainya masuk range koordinat wajar langsung pakai
        if -180 <= f <= 180:
            return "NULL" if f == 0 else f"{round(f, 9)}"
        # Format Excel Dapodik: nilai integer besar → bagi 10^12
        # Contoh: -1420800000000 / 10^12 = -1.4208 (Sulteng lat ~-1.x)
        converted = f / 1_000_000_000_000
        if -180 <= converted <= 180 and converted != 0:
            return f"{round(converted, 9)}"
        return "NULL"
    except Exception:
        return "NULL"

def fix_date(val: str) -> str:
    if val == "NULL":
        return "NULL"
    if val in BAD_DATES:
        return "NULL"
    return val

def fix_string(val: str) -> str:
    if val in BAD_STRINGS:
        return "NULL"
    return val

def parse_values_line(values_str: str) -> list[str]:
    """
    Parse satu baris VALUES (...) dengan memperhatikan string quoted yang mengandung koma.
    Return list of raw SQL tokens (NULL, 'string', number).
    """
    tokens = []
    i = 0
    s = values_str.strip()
    while i < len(s):
        c = s[i]
        if c == ' ' or c == '\t':
            i += 1
        elif c == ',':
            i += 1
        elif c == 'N' and s[i:i+4] == 'NULL':
            tokens.append('NULL')
            i += 4
        elif c == "'":
            # Cari penutup quote, perhatikan escaped quotes
            j = i + 1
            while j < len(s):
                if s[j] == "'" and (j+1 >= len(s) or s[j+1] != "'"):
                    break
                if s[j] == "\\" and j+1 < len(s):
                    j += 2
                    continue
                if s[j] == "'" and j+1 < len(s) and s[j+1] == "'":
                    j += 2
                    continue
                j += 1
            tokens.append(s[i:j+1])
            i = j + 1
        else:
            # Numeric atau identifier
            j = i
            while j < len(s) and s[j] not in (',', ' '):
                j += 1
            tokens.append(s[i:j])
            i = j
    return tokens

def remap_row(tokens: list[str]) -> str:
    """
    Dari list token urutan BACKBONE_COLS → dict → reorder ke TARGET_COLS
    """
    if len(tokens) != len(BACKBONE_COLS):
        return None

    row = dict(zip(BACKBONE_COLS, tokens))

    # Fix koordinat
    for col in COORD_COLS:
        if col in row:
            row[col] = fix_koordinat(row[col], col)

    # Fix tanggal
    for col in DATE_COLS:
        if col in row:
            row[col] = fix_date(row[col])

    # Fix string placeholder
    for col, val in row.items():
        if col not in COORD_COLS and col not in DATE_COLS:
            row[col] = fix_string(val)

    # Reorder ke TARGET_COLS, tambahkan default untuk kolom project
    result = []
    for col in TARGET_COLS:
        if col == "jumlah_siswa":
            result.append("0")
        elif col == "daya_tampung":
            result.append("0")
        elif col == "is_3t":
            result.append("0")
        elif col == "is_sekolah_alam":
            result.append("0")
        else:
            result.append(row.get(col, "NULL"))

    return "(" + ", ".join(result) + ")"

def main():
    print(f"[1/4] Membaca {INPUT_FILE}...")
    with open(INPUT_FILE, "r", encoding="utf-8") as f:
        content = f.read()

    print("[2/4] Parsing INSERT statements...")
    # Ambil semua VALUES dari INSERT backbone_sekolah
    pattern = re.compile(
        r"INSERT INTO `backbone_sekolah` \([^)]+\) VALUES \((.+?)\);",
        re.DOTALL
    )
    matches = pattern.findall(content)
    print(f"      Ditemukan {len(matches)} rows")

    print("[3/4] Transform & remap kolom...")
    col_list = ", ".join(f"`{c}`" for c in TARGET_COLS)
    rows_ok = 0
    rows_skip = 0

    chunk_size = 500
    output_lines = []
    output_lines.append("-- sekolah_import.sql — generated by convert_backbone_sql.py")
    output_lines.append("-- Data backbone sekolah Sulteng, siap di-import ke tabel sekolah")
    output_lines.append("")
    output_lines.append("SET FOREIGN_KEY_CHECKS=0;")
    output_lines.append("TRUNCATE TABLE `sekolah`;")
    output_lines.append("")

    buffer = []
    for i, values_str in enumerate(matches):
        tokens = parse_values_line(values_str)
        remapped = remap_row(tokens)
        if remapped:
            buffer.append(remapped)
            rows_ok += 1
        else:
            rows_skip += 1

        # Tulis chunk
        if len(buffer) >= chunk_size:
            output_lines.append(
                f"INSERT INTO `sekolah` ({col_list}) VALUES\n" +
                ",\n".join(buffer) + ";"
            )
            output_lines.append("")
            buffer = []

        if (i + 1) % 1000 == 0:
            print(f"      {i+1}/{len(matches)} rows diproses...")

    # Sisa buffer
    if buffer:
        output_lines.append(
            f"INSERT INTO `sekolah` ({col_list}) VALUES\n" +
            ",\n".join(buffer) + ";"
        )
        output_lines.append("")

    output_lines.append("SET FOREIGN_KEY_CHECKS=1;")
    output_lines.append("")
    output_lines.append(f"-- Total inserted: {rows_ok}")
    output_lines.append(f"-- Total skipped:  {rows_skip}")

    print(f"[4/4] Menulis {OUTPUT_FILE}...")
    with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
        f.write("\n".join(output_lines))

    print(f"\n✅ Selesai!")
    print(f"   OK     : {rows_ok} rows")
    print(f"   Skipped: {rows_skip} rows")
    print(f"   Output : {OUTPUT_FILE}")

if __name__ == "__main__":
    main()
