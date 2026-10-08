import requests

url = "http://localhost/NNN/B/update_status.php"

devices = [
    {"node_id": 1, "status": "off"},
    {"node_id": 2, "status": "off"},
    {"node_id": 3, "status": "off"},
    {"node_id": 5, "status": "off"}
]

channels = [
    {"node_id": 1, "channel": 1, "status": "off"},
    {"node_id": 1, "channel": 2, "status": "off"},
    {"node_id": 1, "channel": 3, "status": "off"},
    {"node_id": 1, "channel": 4, "status": "off"}
]

print("=== TEST DEVICE ===")

for d in devices:
    r = requests.post(url, data=d)
    print("Node", d["node_id"], ":", r.status_code, r.text)

print("\n=== TEST LED CHANNEL ===")

for c in channels:
    r = requests.post(url, data=c)
    print("Node", c["node_id"], "Channel", c["channel"], ":", r.status_code, r.text)