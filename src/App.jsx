import React from 'react'
import { Thermometer, Droplets, CloudRain, Wind, Activity } from 'lucide-react'

// Header Component
const Header = () => {
  return (
    <header className="bg-dark-green text-white px-8 py-5 shadow-lg">
      <div className="max-w-7xl mx-auto flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Activity className="w-8 h-8" />
          <h1 className="text-2xl font-bold tracking-wide">IoT Weather Station</h1>
        </div>
        <div className="flex items-center gap-2 text-sm opacity-80">
          <CloudRain className="w-4 h-4" />
          <span>Live Monitoring</span>
        </div>
      </div>
    </header>
  )
}

// Environmental Status Card Component
const StatusCard = ({ title, value, unit, icon: Icon, trend }) => {
  return (
    <div className="bg-card-pink rounded-2xl p-8 shadow-md hover:shadow-xl transition-shadow duration-300">
      <div className="flex items-start justify-between">
        <div className="flex-1">
          <p className="text-gray-600 text-sm font-medium uppercase tracking-wider mb-2">{title}</p>
          <div className="flex items-baseline gap-2">
            <span className="text-6xl font-bold text-gray-800">{value}</span>
            <span className="text-2xl text-gray-600 font-medium">{unit}</span>
          </div>
          {trend && (
            <p className={`text-sm mt-3 font-medium ${trend > 0 ? 'text-green-600' : 'text-red-500'}`}>
              {trend > 0 ? '↑' : '↓'} {Math.abs(trend)}% from last hour
            </p>
          )}
        </div>
        <div className="bg-white/50 rounded-full p-4">
          <Icon className="w-10 h-10 text-gray-700" />
        </div>
      </div>
    </div>
  )
}

// Recent Logs Table Component
const LogsTable = ({ logs }) => {
  return (
    <div className="bg-white rounded-2xl shadow-md overflow-hidden">
      <div className="px-8 py-5 border-b border-gray-100">
        <h2 className="text-xl font-bold text-gray-800 flex items-center gap-2">
          <Wind className="w-5 h-5" />
          Recent Logs
        </h2>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-8 py-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">ID</th>
              <th className="px-8 py-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Timestamp</th>
              <th className="px-8 py-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Temperature</th>
              <th className="px-8 py-4 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Humidity</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {logs.map((log) => (
              <tr key={log.id} className="hover:bg-gray-50 transition-colors duration-200">
                <td className="px-8 py-4 text-sm font-medium text-gray-700">#{log.id}</td>
                <td className="px-8 py-4 text-sm text-gray-600">{log.timestamp}</td>
                <td className="px-8 py-4 text-sm font-semibold text-gray-800">{log.temperature}°C</td>
                <td className="px-8 py-4 text-sm font-semibold text-gray-800">{log.humidity}%</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}

// Main App Component
function App() {
  // Sample data for demonstration
  const currentData = {
    temperature: 24.5,
    humidity: 68,
    tempTrend: 2.3,
    humidityTrend: -1.2
  }

  const logsData = [
    { id: 1, timestamp: '2026-03-16 13:55:00', temperature: 24.5, humidity: 68 },
    { id: 2, timestamp: '2026-03-16 13:50:00', temperature: 24.3, humidity: 69 },
    { id: 3, timestamp: '2026-03-16 13:45:00', temperature: 24.1, humidity: 70 },
    { id: 4, timestamp: '2026-03-16 13:40:00', temperature: 23.9, humidity: 71 },
    { id: 5, timestamp: '2026-03-16 13:35:00', temperature: 23.8, humidity: 72 },
  ]

  return (
    <div className="min-h-screen bg-bg-cream">
      <Header />
      
      <main className="max-w-7xl mx-auto px-8 py-10">
        {/* Environmental Status Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
          <StatusCard 
            title="Temperature" 
            value={currentData.temperature} 
            unit="°C"
            icon={Thermometer}
            trend={currentData.tempTrend}
          />
          <StatusCard 
            title="Humidity" 
            value={currentData.humidity} 
            unit="%"
            icon={Droplets}
            trend={currentData.humidityTrend}
          />
        </div>

        {/* Recent Logs Table */}
        <LogsTable logs={logsData} />
      </main>
    </div>
  )
}

export default App
