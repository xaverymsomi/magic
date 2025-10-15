// Authentication Models - Preserving Magic Framework Business Logic
export interface User {
  id: number;
  email: string;
  username?: string;
  permissions: Permission[];
  session_data?: any;
}

export interface Permission {
  id: number;
  name: string;
  module: string;
  action?: string;
}

export interface LoginRequest {
  email: string;
  password: string;
  captcha: string;
  return_url?: string;
}

export interface LoginResponse {
  status: boolean;
  code: number;
  message: string;
  user?: User;
  redirect_url?: string;
  session_id?: string;
}

export interface RecoverPasswordRequest {
  email: string;
}

export interface CaptchaResponse {
  image_data: string;
  session_token: string;
}