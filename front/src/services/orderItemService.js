const BASE_URL = "http://localhost";

export const orderItemService = {
  async getAll() {
    const response = await fetch(`${BASE_URL}/order-items`);
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message);
    }
    return response.json();
  },

  async store(data) {
    const response = await fetch(`${BASE_URL}/order-items`, {
      method: "POST",
      headers: { "Content-type": "application/json" },
      body: JSON.stringify(data),
    });
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message);
    }
    return response.json();
  },

  async delete(id) {
    const response = await fetch(`${BASE_URL}/order-items/${id}`, {
      method: "DELETE",
    });
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message);
    }
  },
};
