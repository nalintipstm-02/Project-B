from fastapi import FastAPI
import mysql.connector
from datetime import datetime, time, timedelta

app = FastAPI()

# ------------------ Database Connection ------------------
def connect_db():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="booking_system"
    )

# ------------------ Query Helper ------------------
def query_all(sql, params=None):
    conn = connect_db()
    cur = conn.cursor(dictionary=True)
    cur.execute(sql, params or ())
    result = cur.fetchall()
    cur.close()
    conn.close()
    return result

# ------------------ API: ดึงข้อมูล booking ปัจจุบันและอนาคต พร้อม node ของ booking ------------------
@app.get("/bookings/upcoming")
async def get_upcoming_bookings():
    try:
        now = datetime.now()
        current_date = now.date()
        current_time = now.time()

        # ดึงเฉพาะ booking ปัจจุบันและอนาคต
        sql_bookings = """
            SELECT id, room_id, booking_date, start_time, end_time, status
            FROM bookings
            WHERE (booking_date > %s) OR (booking_date = %s AND end_time >= %s)
            ORDER BY booking_date ASC, start_time ASC
        """
        bookings = query_all(sql_bookings, (current_date, current_date, current_time))

        # ดึง node ที่ user เลือกสำหรับแต่ละ booking และแปลงเวลา
        for booking in bookings:
            # แปลง start_time
            start = booking['start_time']
            if isinstance(start, time):
                booking['start_time'] = start.strftime("%H:%M:%S")
            elif isinstance(start, timedelta):
                total_seconds = int(start.total_seconds())
                hours = total_seconds // 3600
                minutes = (total_seconds % 3600) // 60
                seconds = total_seconds % 60
                booking['start_time'] = f"{hours:02d}:{minutes:02d}:{seconds:02d}"

            # แปลง end_time
            end = booking['end_time']
            if isinstance(end, time):
                booking['end_time'] = end.strftime("%H:%M:%S")
            elif isinstance(end, timedelta):
                total_seconds = int(end.total_seconds())
                hours = total_seconds // 3600
                minutes = (total_seconds % 3600) // 60
                seconds = total_seconds % 60
                booking['end_time'] = f"{hours:02d}:{minutes:02d}:{seconds:02d}"

            # ดึง node เฉพาะที่ user เลือกสำหรับ booking นี้
            booking_id = booking['id']
            sql_nodes = """
                SELECT n.node_id, n.node_name, n.node_type, n.detail, n.node_status
                FROM node n
                JOIN booking_equipments be ON n.node_id = be.node_id
                WHERE be.booking_id = %s
            """
            nodes = query_all(sql_nodes, (booking_id,))
            booking['nodes'] = nodes

        return {"count": len(bookings), "data": bookings}

    except Exception as e:
        return {"error": str(e)}
