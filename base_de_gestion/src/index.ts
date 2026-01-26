import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import mysql from 'mysql2/promise';

dotenv.config();

const app = express();
const port = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());

// Database connection configuration
const dbConfig = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'succes',
  password: process.env.DB_PASSWORD || 'succes237',
  database: process.env.DB_NAME || 'timetable',
};

// Test database connection
const testConnection = async () => {
  try {
    const connection = await mysql.createConnection(dbConfig);
    console.log('Successfully connected to the database');
    await connection.end();
  } catch (error) {
    console.error('Error connecting to the database:', error);
  }
};

app.get('/', (req, res) => {
  res.json({ message: 'Welcome to base_de_gestion API' });
});

app.get('/api/users', async (req, res) => {
  try {
    const connection = await mysql.createConnection(dbConfig);
    const [rows] = await connection.execute('SELECT id, username, role FROM users');
    await connection.end();
    res.json(rows);
  } catch (error) {
    res.status(500).json({ error: 'Internal Server Error' });
  }
});

app.listen(port, () => {
  console.log(`Server is running on port ${port}`);
  testConnection();
});
