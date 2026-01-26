"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = __importDefault(require("express"));
const cors_1 = __importDefault(require("cors"));
const dotenv_1 = __importDefault(require("dotenv"));
const promise_1 = __importDefault(require("mysql2/promise"));
dotenv_1.default.config();
const app = (0, express_1.default)();
const port = process.env.PORT || 3000;
app.use((0, cors_1.default)());
app.use(express_1.default.json());
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
        const connection = await promise_1.default.createConnection(dbConfig);
        console.log('Successfully connected to the database');
        await connection.end();
    }
    catch (error) {
        console.error('Error connecting to the database:', error);
    }
};
app.get('/', (req, res) => {
    res.json({ message: 'Welcome to base_de_gestion API' });
});
app.get('/api/users', async (req, res) => {
    try {
        const connection = await promise_1.default.createConnection(dbConfig);
        const [rows] = await connection.execute('SELECT id, username, role FROM users');
        await connection.end();
        res.json(rows);
    }
    catch (error) {
        res.status(500).json({ error: 'Internal Server Error' });
    }
});
app.listen(port, () => {
    console.log(`Server is running on port ${port}`);
    testConnection();
});
