# 🌱 Eco Garden

## 📖 Description:
### 🌐 Eco Garden is a Symfony-based project that:
#### 🌤️ Provides weather data for users based on their location or a specified city.
##### 🌿 Offers gardening advice for the current month or a specific month.

## ✨ Features
### - 🌦️ Fetches current weather data from an external API.
### - 🏙️ Allows users to view weather by their city or any other specified city.
### - 🚀 Implements caching for improved performance.

## ⚙️ Installation

### 1. Clone the repository:
#### git clone https://github.com/SnezhanaPashovska/Eco_Garden.git
#### cd Eco_Garden

### 2. Install dependencies:
#### composer install
#### npm install

### 3. Set up environment variables:
#### Rename .env.example to .env and configure the required values (DATABASE_URL, OPENWEATHER_API_KEY).

### 4. Run migrations:
#### php bin/console doctrine:migrations:migrate

### 5. Start the Symfony server:
#### symfony server:start


## 🚀 Usage
### 🌐 Access the application in your browser at http://localhost:8000.
### API Endpoints:
#### GET /api/external/meteo: 🌤️ Fetch weather data for the user's city.
#### GET /api/external/meteo/{city}: 🌆 Fetch weather data for the specified city.
#### GET /api/conseil: 🪴 Fetch advice for the current month.
#### GET /api/conseil/{month}: 📅 Fetch advice for a specific month

### 💻 Technologies Used
##### ⚡ Symfony: Backend framework
##### 🌍 OpenWeather API: External weather service
##### 🐘 PHP: Programming language
##### 🛢️ MySQL: Database


