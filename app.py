import os
import psycopg2
from psycopg2.extras import RealDictCursor
from flask import Flask, render_template, request, jsonify, send_from_directory
from dotenv import load_dotenv

# Load environment variables from .env file
load_dotenv()

app = Flask(__name__, static_folder='frontend', template_folder='frontend')

# Database connection configuration using the hb_app user from Activity 4
DB_HOST = os.getenv("DB_HOST", "localhost")
DB_PORT = os.getenv("DB_PORT", "5432")
DB_NAME = os.getenv("DB_NAME", "grp03_projectdb")
DB_USER = os.getenv("DB_USER", "hb_app")
DB_PASSWORD = os.getenv("DB_PASSWORD", "HealthBridge2026!Secure")

def get_db_connection():
    """Establish a connection to grp03_projectdb using hb_app credentials."""
    conn = psycopg2.connect(
        host=DB_HOST,
        port=DB_PORT,
        dbname=DB_NAME,
        user=DB_USER,
        password=DB_PASSWORD
    )
    return conn

# --- ROUTES TO SERVE HTML PAGES ---
@app.route('/')
def index():
    return send_from_directory('frontend', 'HealthBridge-Patient-Portal.html')

@app.route('/staff')
def staff_portal():
    return send_from_directory('frontend', 'HealthBridge-Staff.html')

@app.route('/admin')
def admin_portal():
    return send_from_directory('frontend', 'HealthBridge-Admin.html')


# --- API ENDPOINTS FOR CRUD OPERATIONS ---

# 1. Get all doctors (SELECT)
@app.route('/api/doctors', methods=['GET'])
def get_doctors():
    conn = get_db_connection()
    cur = conn.cursor(cursor_factory=RealDictCursor)
    cur.execute("SELECT * FROM doctors ORDER BY doctor_id;")
    doctors = cur.fetchall()
    cur.close()
    conn.close()
    return jsonify(doctors)

# 2. Register a new patient (INSERT)
@app.route('/api/patients', methods=['POST'])
def register_patient():
    data = request.json
    conn = get_db_connection()
    cur = conn.cursor()
    
    query = """
        INSERT INTO patients (first_name, last_name, date_of_birth, gender, contact_number, address)
        VALUES (%s, %s, %s, %s, %s, %s)
        RETURNING patient_id;
    """
    cur.execute(query, (
        data.get('first_name'),
        data.get('last_name'),
        data.get('date_of_birth'),
        data.get('gender'),
        data.get('contact_number'),
        data.get('address')
    ))
    patient_id = cur.fetchone()[0]
    conn.commit()
    cur.close()
    conn.close()
    
    return jsonify({"status": "success", "patient_id": patient_id}), 201

# 3. Get appointment queue (SELECT with JOIN)
@app.route('/api/appointments', methods=['GET'])
def get_appointments():
    conn = get_db_connection()
    cur = conn.cursor(cursor_factory=RealDictCursor)
    query = """
        SELECT 
            a.appointment_id,
            p.first_name || ' ' || p.last_name AS patient_name,
            d.first_name || ' ' || d.last_name AS doctor_name,
            a.appointment_date,
            a.status
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        ORDER BY a.appointment_date DESC;
    """
    cur.execute(query)
    appointments = cur.fetchall()
    cur.close()
    conn.close()
    return jsonify(appointments)

if __name__ == '__main__':
    app.run(debug=True, port=5000)
