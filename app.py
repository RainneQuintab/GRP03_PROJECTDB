from flask import Flask, render_template, request, jsonify
# Add your database connection imports here (e.g., psycopg2)

app = Flask(__name__)

@app.route('/')
def index():
    return "Welcome to HealthBridge Clinic Management System API"

# Add your API routes for patients, appointments, and doctors here

if __name__ == '__main__':
    app.run(debug=True)
