const BASE_URL = "http://localhost";

export const orderService = {
  async getAll() {
    const response = await fetch(`${BASE_URL}/orders`);
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message);
    }
    return response.json();
  },

  async cancelUpdate(id) {
    const response = await fetch(`${BASE_URL}/cancel-order/${id}`, {
      method: "PUT",
      headers: { "Content-type": "application/json" },
    });
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message);
    }
  },

  async finishUpdate(id) {
    const response = await fetch(`${BASE_URL}/finish-order/${id}`, {
      method: "PUT",
      headers: { "Content-type": "application/json" },
    })
    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message)
    }
  }
};
