from fastapi import FastAPI
import mysql.connector
from datetime import datetime

app = FastAPI()

# ------------------ Database Connection ------------------
def connect_db():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="booking_system"  #แก้เป็นชื่อฐานข้อมูลของคุณ
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

# ------------------ API: ดึงข้อมูล booking จากเวลาปัจจุบัน ------------------
@app.get("/bookings/upcoming")
async def get_upcoming_bookings():
    """
    🔹 ดึงข้อมูลการจองตั้งแต่เวลาปัจจุบันเป็นต้นไป
    """
    now = datetime.now()
    current_date = now.date()
    current_time = now.time()

    sql = """
        SELECT room_id, booking_date, start_time, end_time, status
        FROM booking
        WHERE 
            (booking_date > %s)
            OR (booking_date = %s AND end_time >= %s)
        ORDER BY booking_date ASC, start_time ASC
    """

    bookings = query_all(sql, (current_date, current_date, current_time))
    return {"count": len(bookings), "data": bookings}
