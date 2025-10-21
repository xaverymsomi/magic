export interface User {
  id: number;
  email: string;
  username: string;
  role: string;
  permissions: Permission[];
  first_name?: string;
  last_name?: string;
  avatar?: string;
}

export interface Permission {
  id: number;
  name: string;
  description?: string;
}

export interface LoginRequest {
  email: string;
  password: string;
  captcha?: string;
  return_url?: string;
}

export interface LoginResponse {
  success: boolean;
  status: boolean;
  code: number;
  message: string;
  user?: User;
  redirect_url?: string;
}

export interface RecoverPasswordRequest {
  email: string;
}
