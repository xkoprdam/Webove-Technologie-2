<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Management API Tester</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        h2 {
            color: #333;
        }
        .form-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        #response {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
<h1>Car Management API Tester</h1>

<!-- GET: Fetch Car Details -->
<div class="form-section">
    <h2>Get Car Details (GET /api/v0/cars/{id})</h2>
    <form id="getCarForm">
        <label for="getCarId">Car ID:</label>
        <input type="number" id="getCarId" name="getCarId" required>
        <button type="submit">Fetch Car</button>
    </form>
</div>

<!-- POST: Create a New Car -->
<div class="form-section">
    <h2>Create a New Car (POST /api/v0/cars)</h2>
    <form id="createCarForm">
        <label for="brand">Brand:</label>
        <input type="text" id="brand" name="brand" required>

        <label for="carType">Car Type:</label>
        <input type="text" id="carType" name="carType" required>

        <label for="serviceRecords">Service Records (JSON array, e.g., [{"problem":"Oil leak","solution":"Replaced gasket","createdAt":"2025-03-25"}]):</label>
        <textarea id="serviceRecords" name="serviceRecords" rows="4" placeholder='[{"problem":"Oil leak","solution":"Replaced gasket","createdAt":"2025-03-25"}]'></textarea>

        <button type="submit">Create Car</button>
    </form>
</div>

<!-- POST: Add a Service Record to a Car -->
<div class="form-section">
    <h2>Add Service Record (POST /api/v0/cars/{id}/service)</h2>
    <form id="addServiceRecordForm">
        <label for="serviceCarId">Car ID:</label>
        <input type="number" id="serviceCarId" name="serviceCarId" required>

        <label for="problem">Problem:</label>
        <input type="text" id="problem" name="problem" required>

        <label for="solution">Solution:</label>
        <input type="text" id="solution" name="solution" required>

        <label for="createdAt">Created At (YYYY-MM-DD):</label>
        <input type="text" id="createdAt" name="createdAt" placeholder="2025-03-25" required>

        <button type="submit">Add Service Record</button>
    </form>
</div>

<!-- DELETE: Delete a Car -->
<div class="form-section">
    <h2>Delete a Car (DELETE /api/v0/cars/{id})</h2>
    <form id="deleteCarForm">
        <label for="deleteCarId">Car ID:</label>
        <input type="number" id="deleteCarId" name="deleteCarId" required>
        <button type="submit">Delete Car</button>
    </form>
</div>

<!-- Response Output -->
<div id="response">Response will appear here...</div>

<script>
    // Helper function to display the response
    function displayResponse(data) {
        const responseDiv = document.getElementById('response');
        responseDiv.textContent = JSON.stringify(data, null, 2);
    }

    // GET: Fetch Car Details
    document.getElementById('getCarForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const carId = document.getElementById('getCarId').value;
        try {
            const response = await fetch(`/zapocet/api/v0/cars/${carId}`, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();
            displayResponse(data);
        } catch (error) {
            displayResponse({ error: 'Failed to fetch car: ' + error.message });
        }
    });

    // POST: Create a New Car
    document.getElementById('createCarForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const brand = document.getElementById('brand').value;
        const carType = document.getElementById('carType').value;
        const serviceRecords = document.getElementById('serviceRecords').value || '[]';

        try {
            const serviceRecordsArray = JSON.parse(serviceRecords);
            const response = await fetch('/zapocet/api/v0/cars', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ brand, carType, serviceRecords: serviceRecordsArray })
            });
            const data = await response.json();
            displayResponse(data);
        } catch (error) {
            displayResponse({ error: 'Failed to create car: ' + error.message });
        }
    });

    // POST: Add a Service Record
    document.getElementById('addServiceRecordForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const carId = document.getElementById('serviceCarId').value;
        const problem = document.getElementById('problem').value;
        const solution = document.getElementById('solution').value;
        const createdAt = document.getElementById('createdAt').value;

        try {
            const response = await fetch(`/zapocet/api/v0/cars/${carId}/service`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ problem, solution, createdAt })
            });
            const data = await response.json();
            displayResponse(data);
        } catch (error) {
            displayResponse({ error: 'Failed to add service record: ' + error.message });
        }
    });

    // DELETE: Delete a Car
    document.getElementById('deleteCarForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const carId = document.getElementById('deleteCarId').value;
        try {
            const response = await fetch(`/zapocet/api/v0/cars/${carId}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();
            displayResponse(data);
        } catch (error) {
            displayResponse({ error: 'Failed to delete car: ' + error.message });
        }
    });
</script>
</body>
</html>