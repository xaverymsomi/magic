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

// Form-related Models
export interface DropdownOption {
  value: any;
  label: string;
  [key: string]: any;
}

export interface FormData {
  [key: string]: any;
  has_extra?: number;
  chkselct?: any;
  account?: string;
  class_data?: string;
  limit_data?: string;
}

export interface FormConfig {
  url: string;
  actionname: string;
  dropdowns: { [key: string]: DropdownOption[] };
  form: FormData;
  disabled: string[];
}

export interface AutoCompleteConfig {
  searchComponent: string;
  controls: any;
  searchKey: string;
  table: string;
  searchColumn: string;
}