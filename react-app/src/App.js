import React from 'react';
import { BrowserRouter as Router } from 'react-router-dom';
import { ApolloProvider } from '@apollo/client';
import client from './utils/graphql';
import { CartProvider } from './context/CartContext';
import './App.css';

// Components
import Header from './components/Header';
import Footer from './components/Footer';

// Routes
import AppRoutes from './routes/AppRoutes';

function App() {
  return (
    <ApolloProvider client={client}>
      <CartProvider>
        <Router>
          <div className="App">
            <Header />
            
            {/* Main Content Area with Routes */}
            <main className="main-content">
              <div className="container-fluid">
                <AppRoutes />
              </div>
            </main>
            
            <Footer />
          </div>
        </Router>
      </CartProvider>
    </ApolloProvider>
  );
}

export default App; 