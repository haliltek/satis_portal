#!/usr/bin/env python3
"""
Read-only MSSQL → MySQL copier for selected tables.

Features
- Connects to SQL Server (READ ONLY usage; only selects).
- Creates destination MySQL tables with compatible types (if missing).
- Copies rows in batches to avoid memory issues.

Requirements
- Python 3.9+
- pip install pyodbc mysql-connector-python
- SQL Server ODBC Driver installed and reachable (for pyodbc)

Usage
  python3 scripts/mssql_to_mysql_copy.py --config scripts/config.example.json
  python3 scripts/mssql_to_mysql_copy.py --config scripts/config.example.json --tables CariEtiketTanimi,StokOzellikleri

Notes
- Ensure the SQL Server user has only SELECT permissions on the source DB.
- This script never issues INSERT/UPDATE/DELETE on the source.
- Destination tables are created only if missing. Set overwrite=false to keep existing.
"""
import argparse
import json
import os
import sys
from typing import Any, Dict, List, Optional, Tuple

try:
    import pyodbc  # type: ignore
except Exception as e:  # pragma: no cover
    print("Missing dependency: pyodbc. Run: pip install pyodbc", file=sys.stderr)
    raise

try:
    import mysql.connector  # type: ignore
except Exception as e:  # pragma: no cover
    print("Missing dependency: mysql-connector-python. Run: pip install mysql-connector-python", file=sys.stderr)
    raise


def debug(msg: str) -> None:
    print(msg)


def load_config(path: str) -> Dict[str, Any]:
    with open(path, "r", encoding="utf-8") as f:
        cfg = json.load(f)
    # Defaults
    cfg.setdefault("source", {})
    cfg.setdefault("target", {})
    cfg.setdefault("options", {})
    cfg.setdefault("tables", [])
    cfg["source"].setdefault("schema", "dbo")
    cfg["target"].setdefault("create_database", False)
    cfg["options"].setdefault("batch_size", 1000)
    cfg["options"].setdefault("overwrite", False)
    cfg["options"].setdefault("engine", "InnoDB")
    cfg["options"].setdefault("charset", "utf8mb4")
    cfg["options"].setdefault("collation", None)
    return cfg


def mssql_connection_string(source_cfg: Dict[str, Any]) -> str:
    # Prefer explicit DRIVER. Example for macOS/Linux with msodbcsql17:
    # DRIVER={ODBC Driver 17 for SQL Server};SERVER=host,1433;DATABASE=GEMAS_DYS;UID=...;PWD=...
    driver = source_cfg.get("driver") or "ODBC Driver 17 for SQL Server"
    server = source_cfg.get("server")
    port = source_cfg.get("port")
    database = source_cfg.get("database")
    uid = source_cfg.get("user")
    pwd = source_cfg.get("password")
    app_intent = source_cfg.get("application_intent", "ReadOnly")

    if not (server and database and uid and pwd):
        raise ValueError("source.server, source.database, source.user, source.password are required")
    server_part = f"{server},{port}" if port else server
    # ApplicationIntent is honored when connecting to Availability Groups; it's still safe to include.
    conn = (
        "DRIVER={{{}}};SERVER={};DATABASE={};UID={};PWD={};ApplicationIntent={};TrustServerCertificate=Yes;".format(
            driver, server_part, database, uid, pwd, app_intent
        )
    )
    return conn


def connect_mssql(source_cfg: Dict[str, Any]) -> pyodbc.Connection:
    conn_str = mssql_connection_string(source_cfg)
    debug("Connecting MSSQL (read-only usage)...")
    cn = pyodbc.connect(conn_str, autocommit=False)
    # Set read-only session if driver supports it (best-effort)
    try:
        SQL_ATTR_ACCESS_MODE = 101  # pyodbc attr
        SQL_MODE_READ_ONLY = 1
        cn.set_attr(SQL_ATTR_ACCESS_MODE, SQL_MODE_READ_ONLY)
    except Exception:
        pass
    return cn


def connect_mysql(target_cfg: Dict[str, Any]) -> mysql.connector.MySQLConnection:
    debug("Connecting MySQL (target)...")
    required = ["host", "database", "user", "password"]
    for k in required:
        if not target_cfg.get(k):
            raise ValueError(f"target.{k} is required")
    conn = mysql.connector.connect(
        host=target_cfg["host"],
        port=target_cfg.get("port", 3306),
        database=target_cfg["database"],
        user=target_cfg["user"],
        password=target_cfg["password"],
        autocommit=False,
        charset=target_cfg.get("charset", "utf8mb4"),
        use_pure=True,
    )
    return conn


def fetch_columns_mssql(cn: pyodbc.Connection, db: str, schema: str, table: str) -> List[Dict[str, Any]]:
    sql = """
    SELECT
      c.COLUMN_NAME,
      c.DATA_TYPE,
      c.CHARACTER_MAXIMUM_LENGTH,
      c.NUMERIC_PRECISION,
      c.NUMERIC_SCALE,
      c.IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS c
    WHERE c.TABLE_CATALOG = ? AND c.TABLE_SCHEMA = ? AND c.TABLE_NAME = ?
    ORDER BY c.ORDINAL_POSITION
    """
    cur = cn.cursor()
    cur.execute(sql, (db, schema, table))
    rows = cur.fetchall()
    cols = []
    for r in rows:
        cols.append(
            {
                "COLUMN_NAME": r[0],
                "DATA_TYPE": r[1],
                "CHARACTER_MAXIMUM_LENGTH": r[2],
                "NUMERIC_PRECISION": r[3],
                "NUMERIC_SCALE": r[4],
                "IS_NULLABLE": r[5],
            }
        )
    if not cols:
        raise RuntimeError(f"No columns found for {schema}.{table} in {db}")
    return cols


def fetch_pk_mssql(cn: pyodbc.Connection, db: str, schema: str, table: str) -> List[str]:
    sql = """
    SELECT kcu.COLUMN_NAME
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
    JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
      ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
     AND tc.TABLE_SCHEMA = kcu.TABLE_SCHEMA
     AND tc.TABLE_NAME = kcu.TABLE_NAME
    WHERE tc.TABLE_CATALOG = ?
      AND tc.TABLE_SCHEMA = ?
      AND tc.TABLE_NAME = ?
      AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
    ORDER BY kcu.ORDINAL_POSITION
    """
    cur = cn.cursor()
    cur.execute(sql, (db, schema, table))
    return [r[0] for r in cur.fetchall()]


def map_sqlserver_type_to_mysql(col: Dict[str, Any]) -> str:
    dt = str(col["DATA_TYPE"]).lower()
    length = col["CHARACTER_MAXIMUM_LENGTH"]
    precision = col["NUMERIC_PRECISION"]
    scale = col["NUMERIC_SCALE"]

    if dt in ("int",):
        return "INT"
    if dt in ("bigint",):
        return "BIGINT"
    if dt in ("smallint",):
        return "SMALLINT"
    if dt in ("tinyint",):
        return "TINYINT"
    if dt in ("bit",):
        return "TINYINT(1)"
    if dt in ("real",):
        return "FLOAT"
    if dt in ("float",):
        return "DOUBLE"
    if dt in ("decimal", "numeric", "money", "smallmoney"):
        p = precision or 19
        s = scale if scale is not None else 4
        return f"DECIMAL({int(p)},{int(s)})"
    if dt in ("char", "nchar"):
        if length == -1:
            return "LONGTEXT"
        return f"CHAR({int(length)})"
    if dt in ("varchar", "nvarchar"):
        if length == -1:
            return "LONGTEXT"
        return f"VARCHAR({int(length)})"
    if dt in ("text", "ntext"):
        return "LONGTEXT"
    if dt in ("varbinary",):
        if length == -1:
            return "LONGBLOB"
        return f"VARBINARY({int(length)})"
    if dt in ("binary",):
        return f"BINARY({int(length)})" if length and length > 0 else "BINARY(1)"
    if dt in ("image",):
        return "LONGBLOB"
    if dt in ("date",):
        return "DATE"
    if dt in ("smalldatetime", "datetime"):
        return "DATETIME"
    if dt in ("datetime2",):
        return "DATETIME(6)"
    if dt in ("time",):
        return "TIME"
    if dt in ("uniqueidentifier",):
        return "CHAR(36)"
    if dt in ("xml",):
        return "LONGTEXT"
    # Fallback
    return "LONGTEXT"


def mysql_table_exists(cur, table: str) -> bool:
    cur.execute("SHOW TABLES LIKE %s", (table,))
    return cur.fetchone() is not None


def quote_ident_mysql(name: str) -> str:
    return f"`{name.replace('`','``')}`"


def build_create_table_mysql(table: str, columns: List[Dict[str, Any]], pk_cols: List[str], opts: Dict[str, Any]) -> str:
    cols_sql = []
    for col in columns:
        name = col["COLUMN_NAME"]
        null_ok = str(col["IS_NULLABLE"]).upper() == "YES"
        mapped = map_sqlserver_type_to_mysql(col)
        cols_sql.append(f"{quote_ident_mysql(name)} {mapped} {'NULL' if null_ok else 'NOT NULL'}")

    pk_sql = f", PRIMARY KEY ({', '.join(quote_ident_mysql(c) for c in pk_cols)})" if pk_cols else ""
    engine = opts.get("engine", "InnoDB")
    charset = opts.get("charset", "utf8mb4")
    collation = opts.get("collation")
    collate_sql = f" COLLATE={collation}" if collation else ""
    ddl = (
        f"CREATE TABLE {quote_ident_mysql(table)} (\n  "
        + ",\n  ".join(cols_sql)
        + pk_sql
        + f"\n) ENGINE={engine} DEFAULT CHARSET={charset}{collate_sql};"
    )
    return ddl


def copy_table(
    mssql_cn: pyodbc.Connection,
    mysql_cn: mysql.connector.MySQLConnection,
    db: str,
    schema: str,
    table: str,
    options: Dict[str, Any],
) -> None:
    debug(f"\n=== {schema}.{table} ===")
    cols = fetch_columns_mssql(mssql_cn, db, schema, table)
    pk_cols = fetch_pk_mssql(mssql_cn, db, schema, table)

    mycur = mysql_cn.cursor()
    exists = mysql_table_exists(mycur, table)
    if not exists:
        ddl = build_create_table_mysql(table, cols, pk_cols, options)
        debug(f"Creating table {table} in MySQL...")
        mycur.execute(ddl)
        mysql_cn.commit()
    else:
        if options.get("overwrite"):
            debug(f"Overwriting table {table} (dropping + recreating)...")
            mycur.execute(f"DROP TABLE {quote_ident_mysql(table)}")
            mysql_cn.commit()
            ddl = build_create_table_mysql(table, cols, pk_cols, options)
            mycur.execute(ddl)
            mysql_cn.commit()
        else:
            debug(f"Table {table} already exists; appending rows.")

    # Prepare select and insert
    col_names = [c["COLUMN_NAME"] for c in cols]
    src_select = (
        f"SELECT "
        + ", ".join(f"[{n}]" for n in col_names)
        + f" FROM [{schema}].[{table}]"
    )
    placeholders = ", ".join(["%s"] * len(col_names))
    insert_sql = f"INSERT INTO {quote_ident_mysql(table)} (" + ", ".join(quote_ident_mysql(n) for n in col_names) + f") VALUES ({placeholders})"

    batch_size = int(options.get("batch_size", 1000))
    cur = mssql_cn.cursor()
    cur.fast_executemany = False  # select only; not used on mssql cursor
    cur.execute(src_select)

    total = 0
    batch: List[Tuple[Any, ...]] = []
    while True:
        rows = cur.fetchmany(batch_size)
        if not rows:
            break
        for r in rows:
            batch.append(tuple(r))
        mycur.executemany(insert_sql, batch)
        mysql_cn.commit()
        total += len(batch)
        debug(f"Inserted {len(batch)} rows into {table} (total {total})")
        batch.clear()

    debug(f"Done {table}: {total} rows copied.")


def main() -> None:
    parser = argparse.ArgumentParser(description="Copy tables from MSSQL to MySQL (read-only source)")
    parser.add_argument("--config", required=True, help="Path to JSON config")
    parser.add_argument("--tables", help="Comma-separated override list of tables to copy")
    args = parser.parse_args()

    cfg = load_config(args.config)
    source = cfg["source"]
    target = cfg["target"]
    options = cfg["options"]
    tables: List[str] = []
    if args.tables:
        tables = [t.strip() for t in args.tables.split(",") if t.strip()]
    else:
        tables = cfg.get("tables", [])
    if not tables:
        raise SystemExit("No tables specified. Use --tables or config.tables")

    mssql_cn = connect_mssql(source)
    mysql_cn = connect_mysql(target)
    try:
        for t in tables:
            copy_table(mssql_cn, mysql_cn, source["database"], source.get("schema", "dbo"), t, options)
    finally:
        try:
            mysql_cn.close()
        except Exception:
            pass
        try:
            mssql_cn.close()
        except Exception:
            pass


if __name__ == "__main__":
    main()
