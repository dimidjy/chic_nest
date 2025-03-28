import React from 'react';
import { useCart } from '../../context/CartContext';
import './Cart.css';

const Cart = () => {
  const { 
    cart, 
    loading, 
    error, 
    removeCartItem, 
    updateCartItem, 
    clearCart, 
    checkout
  } = useCart();

  if (loading) {
    return <div className="cart-loading">Loading your cart...</div>;
  }

  if (error) {
    return <div className="cart-error">Error: {error}</div>;
  }

  if (!cart || !cart.items || cart.items.length === 0) {
    return <div className="cart-empty">Your cart is empty</div>;
  }

  const handleQuantityChange = (itemId, newQuantity) => {
    if (newQuantity <= 0) {
      removeCartItem(itemId);
    } else {
      updateCartItem(itemId, newQuantity);
    }
  };

  const handleCheckout = async () => {
    try {
      // Call the checkout function from the context
      const checkoutData = await checkout();
      
      // Instead of using the redirect_url from the API, navigate to our React checkout page
      window.location.href = '/checkout';
    } catch (err) {
      console.error('Checkout error:', err);
    }
  };

  return (
    <div className="cart-container">
      <h2>Your Cart</h2>
      
      <ul className="cart-items">
        {cart.items.map((item) => (
          <li key={item.id} className="cart-item">
            <div className="item-details">
              {item.image && (
                <div className="item-image">
                  <img src={item.image} alt={item.title} />
                </div>
              )}
              <div className="item-info">
                <h3>{item.title}</h3>
                <p className="item-price">${parseFloat(item.unit_price).toFixed(2)}</p>
                
                <div className="quantity-controls">
                  <button 
                    onClick={() => handleQuantityChange(item.id, parseInt(item.quantity) - 1)}
                    className="quantity-btn"
                    aria-label="Decrease quantity"
                  >
                    -
                  </button>
                  <span className="quantity">{item.quantity}</span>
                  <button 
                    onClick={() => handleQuantityChange(item.id, parseInt(item.quantity) + 1)}
                    className="quantity-btn"
                    aria-label="Increase quantity"
                  >
                    +
                  </button>
                </div>
              </div>
            </div>
            
            <div className="item-actions">
              <button 
                onClick={() => removeCartItem(item.id)}
                className="remove-btn"
              >
                Remove
              </button>
            </div>
          </li>
        ))}
      </ul>
      
      <div className="cart-summary">
        <div className="cart-total">
          <span>Total:</span>
          <span>${parseFloat(cart.total).toFixed(2)}</span>
        </div>
        
        <div className="cart-actions">
          <button 
            onClick={clearCart}
            className="clear-cart-btn"
          >
            Clear Cart
          </button>
          
          <button 
            onClick={handleCheckout}
            className="checkout-btn"
          >
            Proceed to Checkout
          </button>
        </div>
      </div>
    </div>
  );
};

export default Cart; 