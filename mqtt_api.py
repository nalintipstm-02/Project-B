import json
import paho.mqtt.client as mqtt
from flask import Flask, request, jsonify
from datetime import datetime

app = Flask(__name__)

#MQTT_BROKER = "172.20.10.10"
MQTT_BROKER = "192.168.1.101"
MQTT_PORT = 1883
MQTT_TOPIC = "sync/event"


def publish_sync_event(table_name):
    payload = {
        "table": table_name,
        "event": "changed",
        "time": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    }

    client = mqtt.Client()
    client.connect(MQTT_BROKER, MQTT_PORT, 60)
    client.publish(MQTT_TOPIC, json.dumps(payload))
    client.disconnect()

    print("[MQTT PUBLISH] Sent:", payload)


@app.route("/trigger-sync", methods=["POST"])
def trigger_sync():
    data = request.json
    table = data.get("table")

    if not table:
        return jsonify({"error": "No table provided"}), 400

    publish_sync_event(table)
    return jsonify({"status": "ok"})


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5001)