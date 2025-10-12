import Axios from 'axios';

const axios = Axios.create({
  baseURL: "http://127.0.0.1:8000/api",
  withCredentials: true, // Ważne dla uwierzytelniania z Laravel Sanctum
});

export default axios;