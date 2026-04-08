import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import { orderService } from "../../services/orderService.js";

export const fetchOrders = createAsyncThunk(
  "orders/fetchAll",
  async (_, { rejectWithValue }) => {
    try {
      return await orderService.getAll();
    } catch (error) {
      return rejectWithValue(error.message);
    }
  },
);

export const cancelOrder = createAsyncThunk(
  "orders/cancel",
  async (id, { rejectWithValue }) => {
    try {
      return await orderService.cancelUpdate(id);
    } catch (error) {
      return rejectWithValue(error.message);
    }
  },
);

export const finishOrder = createAsyncThunk(
  "orders/finish",
  async (id, { rejectWithValue }) => {
    try {
      return await orderService.finishUpdate(id);
    } catch (error) {
      return rejectWithValue(error.message);
    }
  },
);

const orderSlice = createSlice({
  name: "orders",

  initialState: {
    items: [],
    loading: false,
    error: null,
  },

  reducers: {
    clearError(state) {
      state.error = null;
    },
  },

  extraReducers: (builder) => {
    builder
      .addCase(fetchOrders.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchOrders.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload;
      })
      .addCase(fetchOrders.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(cancelOrder.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(cancelOrder.fulfilled, (state, action) => {
        state.loading = false;
        state.items = state.items.filter((o) => o.code !== action.payload);
      })
      .addCase(cancelOrder.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(finishOrder.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(finishOrder.fulfilled, (state, action) => {
        state.loading = false;
        state.items = state.items.filter((o) => o.code !== action.payload);
      })
      .addCase(finishOrder.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      });
  },
});

export const { clearError } = orderSlice.actions;
export default orderSlice.reducer;
