import {configureStore} from '@reduxjs/toolkit';
import categoryReducer from './slices/categorySlice.js';
import productReducer from './slices/productSlice.js'

export const store = configureStore({
  reducer: {
    categories: categoryReducer,
    products: productReducer
  }
})