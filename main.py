from fastapi import FastAPI, Depends
from sqlalchemy import create_engine, Column, Integer, String, ForeignKey, Time, Date
from sqlalchemy.orm import sessionmaker, declarative_base, relationship, Session

DATABASE_URL = "mysql+pymysql://root@localhost/booking_system"
engine = create_engine(DATABASE_URL, echo=True)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()

# --- Models ---
class Room(Base):
    __tablename__ = "room"  # ตรงกับ table จริง
    room_id = Column(Integer, primary_key=True)
    room_name = Column(String(255))
    floor_number = Column(Integer)
    room_type = Column(String(50))
    room_status = Column(String(50))
    building_id = Column(Integer)

    bookings = relationship("Booking", back_populates="room")


class Booking(Base):
    __tablename__ = "bookings"
    id = Column(Integer, primary_key=True)
    user_id = Column(Integer)
    first_name = Column(String(255))
    last_name = Column(String(255))
    room_id = Column(Integer, ForeignKey("room.room_id"))
    booking_date = Column(Date)
    start_time = Column(Time)
    end_time = Column(Time)
    status = Column(String(50))

    room = relationship("Room", back_populates="bookings")
    equipments = relationship("BookingEquipment", back_populates="booking")


class BookingEquipment(Base):
    __tablename__ = "booking_equipments"
    id = Column(Integer, primary_key=True, autoincrement=True)
    booking_id = Column(Integer, ForeignKey("bookings.id"))
    equipment_id = Column(Integer)

    booking = relationship("Booking", back_populates="equipments")


# --- FastAPI App ---
app = FastAPI()

# Dependency
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()


@app.get("/bookings/active")
def get_active_bookings(db: Session = Depends(get_db)):
    # ดึงเฉพาะ booking status approved
    bookings = db.query(Booking).filter(Booking.status == "approved").all()
    result = []
    for b in bookings:
        result.append({
            "id": b.id,
            "user_id": b.user_id,
            "first_name": b.first_name,
            "last_name": b.last_name,
            "room_id": b.room_id,
            "room_name": b.room.room_name if b.room else None,
            "booking_date": b.booking_date,
            "start_time": b.start_time,
            "end_time": b.end_time,
            "status": b.status,
            "equipment_ids": [e.equipment_id for e in b.equipments]
        })
    return result
