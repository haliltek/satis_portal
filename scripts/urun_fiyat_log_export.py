#!/usr/bin/env python3
"""Export product price changes within a date range to an Excel file.

This script queries the `urun_fiyat_log` table and groups domestic and export
price changes for each stock code. The database connection information is
loaded from environment variables (`DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`,
`DB_NAME`). If a `.env` file exists one directory above this script, its
values are loaded automatically.

Example:
    python scripts/urun_fiyat_log_export.py --start-date 2025-08-13 --end-date 2025-08-14
"""

import argparse
import os
from collections import defaultdict
from datetime import datetime, timedelta

import pymysql
from openpyxl import Workbook
try:
    from dotenv import load_dotenv
except ImportError:  # pragma: no cover - module provided via pip
    load_dotenv = None


def load_config():
    """Load database configuration from environment variables."""
    env_path = os.path.join(os.path.dirname(__file__), "..", ".env")
    if load_dotenv and os.path.exists(env_path):
        load_dotenv(env_path)

    return {
        "host": os.getenv("DB_HOST", "localhost"),
        "port": int(os.getenv("DB_PORT", 3306)),
        "user": os.getenv("DB_USER", ""),
        "password": os.getenv("DB_PASS", ""),
        "database": os.getenv("DB_NAME", ""),
    }


def fetch_logs(conn, start, end):
    """Fetch price log rows between `start` and `end` datetimes."""
    sql = (
        "SELECT stokkodu, onceki_fiyat, yeni_fiyat, fiyat_tipi, guncelleme_tarihi "
        "FROM urun_fiyat_log "
        "WHERE guncelleme_tarihi BETWEEN %s AND %s "
        "ORDER BY stokkodu, guncelleme_tarihi"
    )
    with conn.cursor() as cur:
        cur.execute(sql, (start, end))
        return cur.fetchall()


def prepare_rows(rows):
    """Pivot domestic and export prices for each stock code and date."""
    grouped = defaultdict(
        lambda: {
            "stokkodu": None,
            "guncelleme_tarihi": None,
            "domestic_old": None,
            "domestic_new": None,
            "export_old": None,
            "export_new": None,
        }
    )

    for stokkodu, onceki, yeni, fiyat_tipi, guncelleme_tarihi in rows:
        key = (stokkodu, guncelleme_tarihi)
        entry = grouped[key]
        entry["stokkodu"] = stokkodu
        entry["guncelleme_tarihi"] = guncelleme_tarihi
        if fiyat_tipi == "domestic":
            entry["domestic_old"] = onceki
            entry["domestic_new"] = yeni
        elif fiyat_tipi == "export":
            entry["export_old"] = onceki
            entry["export_new"] = yeni
    return list(grouped.values())


def write_excel(data, path):
    """Write the prepared data to an Excel file."""
    wb = Workbook()
    ws = wb.active
    ws.title = "price_changes"
    headers = [
        "stokkodu",
        "guncelleme_tarihi",
        "domestic_old",
        "domestic_new",
        "export_old",
        "export_new",
    ]
    ws.append(headers)
    for row in data:
        ws.append(
            [
                row["stokkodu"],
                row["guncelleme_tarihi"].strftime("%Y-%m-%d %H:%M:%S"),
                row["domestic_old"],
                row["domestic_new"],
                row["export_old"],
                row["export_new"],
            ]
        )
    wb.save(path)


def main():
    parser = argparse.ArgumentParser(
        description="Export price changes from urun_fiyat_log to an Excel file."
    )
    parser.add_argument(
        "--start-date", help="Start date (YYYY-MM-DD). Defaults to yesterday.", type=str
    )
    parser.add_argument(
        "--end-date", help="End date (YYYY-MM-DD). Defaults to today.", type=str
    )
    parser.add_argument(
        "--output", help="Path to output Excel file.", default=None
    )
    args = parser.parse_args()

    end_date = (
        datetime.strptime(args.end_date, "%Y-%m-%d") if args.end_date else datetime.now()
    )
    start_date = (
        datetime.strptime(args.start_date, "%Y-%m-%d")
        if args.start_date
        else end_date - timedelta(days=1)
    )
    start_dt = start_date.strftime("%Y-%m-%d 00:00:00")
    end_dt = end_date.strftime("%Y-%m-%d 23:59:59")
    output = (
        args.output
        or f"price_changes_{start_date.strftime('%Y%m%d')}_{end_date.strftime('%Y%m%d')}.xlsx"
    )

    cfg = load_config()
    conn = pymysql.connect(
        host=cfg["host"],
        port=cfg["port"],
        user=cfg["user"],
        password=cfg["password"],
        database=cfg["database"],
        charset="utf8mb4",
    )
    try:
        rows = fetch_logs(conn, start_dt, end_dt)
    finally:
        conn.close()

    data = prepare_rows(rows)
    write_excel(data, output)
    print(f"Wrote {len(data)} rows to {output}")


if __name__ == "__main__":
    main()
