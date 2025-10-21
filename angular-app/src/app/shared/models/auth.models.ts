export interface User {
  id: number;
  username: string;
  email: string;
  role: string;
  avatar?: string;
  permissions: Permission[];
}

export interface Permission {
  id: number;
  name: string;
  module: string;
}

export interface LoginRequest {
  email: string;
  password: string;
  captcha: string;
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

export interface AuthResponse {
  status: boolean;
  code: number;
  message: string;
  user?: User;
  token?: string;
}
