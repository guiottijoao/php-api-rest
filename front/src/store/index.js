import { configureStore } from "@reduxjs/toolkit";
import categoryReducer from "./slices/categorySlice.js";
import productReducer from "./slices/productSlice.js";
import orderItemReducer from "./slices/orderItemSlice.js";
import orderReducer from "./slices/orderSlice.js";

export const store = configureStore({
  reducer: {
    categories: categoryReducer,
    products: productReducer,
    orderItems: orderItemReducer,
    orders: orderReducer,
  },
});
