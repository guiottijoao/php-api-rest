const BASE_URL = "http://localhost";

export const categoryService = {
  async getAll() {
    const response = await fetch(`${BASE_URL}/categories`);
    if (!response.ok) throw new Error("Failed to fetch categories.");
    return response.json();
  },

  async store(data) {
    const response = await fetch(`${BASE_URL}/categories`, {
      method: "POST",
      headers: { "Content-type": "application/json" },
      body: JSON.stringify(data),
    });
    if (!response.ok) throw new Error("Failed to create category.");
    return response.json();
  },

  async delete(id) {
    const response = await fetch(`${BASE_URL}/categories/${id}`, {
      method: 'DELETE'
    });
    if (!response.ok) throw new Error("Failed to delete category.");
  },
};
