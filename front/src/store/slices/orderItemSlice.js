import { createSlice, createAsyncThunk } from "@reduxjs/toolkit";
import { orderItemService } from "../../services/orderItemService";

export const fetchOrderItems = createAsyncThunk(
  "orderItems/fetchAll",
  async (_, { rejectWithValue }) => {
    try {
      return await orderItemService.getAll();
    } catch (error) {
      return rejectWithValue(error.message);
    }
  },
);

export const createOrderItem = createAsyncThunk(
  "orderItems/create",
  async (data, { rejectWithValue }) => {
    try {
      return await orderItemService.store(data);
    } catch (error) {
      return rejectWithValue(error.message);
    }
  },
);

export const deleteOrderItem = createAsyncThunk(
  "orderItems/delete",
  async (id, { rejectWithValue }) => {
    try {
      await orderItemService.delete(id);
      return id;
    } catch (error) {
      return rejectWithValue(error.message);
    }
  },
);

const orderItemSlice = createSlice({
  name: "orderItems",

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
      .addCase(fetchOrderItems.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchOrderItems.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload;
      })
      .addCase(fetchOrderItems.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(createOrderItem.fulfilled, (state, action) => {
        state.items.push(action.payload.data);
      })
      .addCase(createOrderItem.rejected, (state, action) => {
        state.error = action.payload;
      })
      .addCase(deleteOrderItem.fulfilled, (state, action) => {
        state.items = state.items.filter((c) => c.code !== action.payload);
      })
      .addCase(deleteOrderItem.rejected, (state, action) => {
        state.error = action.payload;
      });
  },
});

export const { clearError } = orderItemSlice.actions;
export default orderItemSlice.reducer;
