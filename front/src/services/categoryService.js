const BASE_URL = "http://localhost";

export const categoryService = {
  async getAll() {
    const response = await fetch(`${BASE_URL}/categories`);
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message);
    }
    return response.json();
  },

  async store(data) {
    const response = await fetch(`${BASE_URL}/categories`, {
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
    const response = await fetch(`${BASE_URL}/categories/${id}`, {
      method: "DELETE",
    });
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message);
    }
  },
};
