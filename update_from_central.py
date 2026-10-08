import mysql.connector
from mysql.connector import Error

# ===============================
# DATABASE CONFIG
# ===============================
DB_SOURCE = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "project"
}

DB_DEST = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "booking_system"
}


def sync_data():

    conn_p = None
    conn_b = None

    try:

        conn_p = mysql.connector.connect(**DB_SOURCE)
        conn_b = mysql.connector.connect(**DB_DEST)

        cursor_p = conn_p.cursor(dictionary=True)
        cursor_b = conn_b.cursor()

        print("\n===== เริ่ม Sync Database =====")

        # ==================================================
        # 1️⃣ SYNC BUILDING
        # ==================================================
        cursor_p.execute("SELECT building_id, building_name FROM building")
        buildings = cursor_p.fetchall()

        for b in buildings:

            cursor_b.execute("""
                INSERT INTO building (building_id, building_name)
                VALUES (%s,%s)
                ON DUPLICATE KEY UPDATE
                building_name = VALUES(building_name)
            """, (
                b["building_id"],
                b["building_name"]
            ))

        conn_b.commit()
        print(f"✔ Sync อาคาร {len(buildings)} รายการ")


        # ==================================================
        # 2️⃣ SYNC ROOM
        # ==================================================
        cursor_p.execute("""
            SELECT room_id, room_name, floor_number, room_type
            FROM room
        """)

        rooms = cursor_p.fetchall()

        for r in rooms:

            cursor_b.execute("""
                INSERT INTO room
                (room_id, room_name, floor_number, room_type, room_status)
                VALUES (%s,%s,%s,%s,'available')
                ON DUPLICATE KEY UPDATE
                    room_name = VALUES(room_name),
                    floor_number = VALUES(floor_number),
                    room_type = VALUES(room_type)
            """, (
                int(r["room_id"]),
                r["room_name"],
                r["floor_number"],
                r["room_type"]
            ))

        conn_b.commit()
        print(f"✔ Sync ห้อง {len(rooms)} รายการ")


        # ==================================================
        # 3️⃣ SYNC NODE (เพิ่ม computer_role)
        # ==================================================
        cursor_p.execute("""
            SELECT 
                node_id,
                node_name,
                node_type,
                detail,
                node_status,
                computer_role
            FROM node
        """)

        nodes = cursor_p.fetchall()

        for n in nodes:

            cursor_b.execute("""
                INSERT INTO node
                (node_id,node_name,node_type,detail,node_status,computer_role)
                VALUES (%s,%s,%s,%s,%s,%s)
                ON DUPLICATE KEY UPDATE
                    node_name = VALUES(node_name),
                    node_type = VALUES(node_type),
                    detail = VALUES(detail),
                    node_status = VALUES(node_status),
                    computer_role = VALUES(computer_role)
            """, (
                n["node_id"],
                n["node_name"],
                n["node_type"],
                n["detail"],
                n["node_status"],
                n["computer_role"]
            ))

        conn_b.commit()
        print(f"✔ Sync อุปกรณ์ {len(nodes)} รายการ")


        # ==================================================
        # 4️⃣ SYNC INSTALLATION_NODE
        # ==================================================
        cursor_p.execute("""
            SELECT node_id, room_id, status
            FROM installation_node
        """)

        installs = cursor_p.fetchall()

        for i in installs:

            cursor_b.execute("""
                DELETE FROM installation_node
                WHERE node_id = %s
            """, (i["node_id"],))

            cursor_b.execute("""
                INSERT INTO installation_node
                (node_id, room_id, status)
                VALUES (%s,%s,%s)
            """, (
                i["node_id"],
                int(i["room_id"]),
                i["status"]
            ))

        conn_b.commit()
        print(f"✔ Sync Installation {len(installs)} รายการ")


        # ==================================================
        # 5️⃣ DELETE NODE ที่ต้นทางลบแล้ว
        # ==================================================
        cursor_p.execute("SELECT node_id FROM node")
        source_nodes = {row["node_id"] for row in cursor_p.fetchall()}

        cursor_b.execute("SELECT node_id FROM node")
        dest_nodes = {row[0] for row in cursor_b.fetchall()}

        deleted_nodes = dest_nodes - source_nodes

        for node_id in deleted_nodes:

            cursor_b.execute(
                "DELETE FROM installation_node WHERE node_id=%s",
                (node_id,)
            )

            cursor_b.execute(
                "DELETE FROM node WHERE node_id=%s",
                (node_id,)
            )

        conn_b.commit()
        print(f"✔ ลบ Node ที่หายไป {len(deleted_nodes)} รายการ")


        # ==================================================
        # 6️⃣ DELETE ROOM ที่ต้นทางลบแล้ว
        # ==================================================
        cursor_p.execute("SELECT room_id FROM room")
        source_rooms = {int(row["room_id"]) for row in cursor_p.fetchall()}

        cursor_b.execute("SELECT room_id FROM room")
        dest_rooms = {row[0] for row in cursor_b.fetchall()}

        deleted_rooms = dest_rooms - source_rooms

        for room_id in deleted_rooms:

            cursor_b.execute(
                "DELETE FROM installation_node WHERE room_id=%s",
                (room_id,)
            )

            cursor_b.execute(
                "DELETE FROM room WHERE room_id=%s",
                (room_id,)
            )

        conn_b.commit()
        print(f"✔ ลบ Room ที่หายไป {len(deleted_rooms)} รายการ")


        # ==================================================
        # 7️⃣ DELETE BUILDING ที่ต้นทางลบแล้ว
        # ==================================================
        cursor_p.execute("SELECT building_id FROM building")
        source_buildings = {row["building_id"] for row in cursor_p.fetchall()}

        cursor_b.execute("SELECT building_id FROM building")
        dest_buildings = {row[0] for row in cursor_b.fetchall()}

        deleted_buildings = dest_buildings - source_buildings

        for building_id in deleted_buildings:

            cursor_b.execute(
                "DELETE FROM building WHERE building_id=%s",
                (building_id,)
            )

        conn_b.commit()
        print(f"✔ ลบ Building ที่หายไป {len(deleted_buildings)} รายการ")


        print("===== Sync สำเร็จ =====\n")


    except Error as e:

        print("Database Error:", e)

        if conn_b:
            conn_b.rollback()


    finally:

        if conn_p and conn_p.is_connected():
            cursor_p.close()
            conn_p.close()

        if conn_b and conn_b.is_connected():
            cursor_b.close()
            conn_b.close()

        print("ปิดการเชื่อมต่อ Database เรียบร้อย")


# ===============================
# RUN SCRIPT
# ===============================
if __name__ == "__main__":
    sync_data()