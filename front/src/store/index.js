import {configureStore} from '@reduxjs/toolkit';
import categoryReducer from './slices/categorySlice.js';
import productReducer from './slices/productSlice.js'
import orderItemReducer from './slices/orderItemSlice.js'

export const store = configureStore({
  reducer: {
    categories: categoryReducer,
    products: productReducer,
    orderItem: orderItemReducer
  }
})