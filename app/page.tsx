// app/page.tsx

"use client";

import React, { useState, useEffect } from 'react';
import Image from 'next/image';
import {
  Moon, Sun, Menu, X, Layout, ShoppingCart, ArrowRight,
  Github, Linkedin, Mail, Layers, Palette as PaletteIcon,
  Instagram, Youtube, Facebook, MessageCircle, Globe, MapPin, Phone
} from 'lucide-react';

import ProjectsArea from '../components/ProjectsArea';
import DynamicForm from '../components/DynamicForm';
import { API_BASE_URL, IMG_BASE_URL } from '../utils/api';

// Definição tipada dos dados que vêm da API
interface SocialLink {
  platform: string;
  url: string;
}

interface SiteSettings {
  site_title?: string;
  site_description?: string;
  site_logo?: string;
  contact_email?: string;
  contact_phone?: string;
  contact_address?: string;
  social_links?: SocialLink[];
}

export default function Home() {
  const [isDarkMode, setIsDarkMode] = useState(true);
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const [isLoadingLogo, setIsLoadingLogo] = useState(true);
  const [siteSettings, setSiteSettings] = useState<SiteSettings | null>(null);

  useEffect(() => {
    const handleScroll = () => setScrolled(window.scrollY > 20);
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  useEffect(() => {
    async function fetchSettings() {
      try {
        setIsLoadingLogo(true);
        const res = await fetch(`${API_BASE_URL}/api_settings.php`);
        if (!res.ok) throw new Error('Erro na API');
        const json = await res.json();
        
        if (json.success && json.data) {
          setSiteSettings(json.data);
        }
      } catch (err) {
        console.error('Erro ao carregar configurações:', err);
      } finally {
        setIsLoadingLogo(false);
      }
    }
    fetchSettings();
  }, []);

  const toggleTheme = () => setIsDarkMode(!isDarkMode);

  // Função auxiliar para renderizar ícone baseado na string da plataforma
  const getSocialIcon = (platform: string) => {
    const p = platform.toLowerCase();
    const size = 20;
    if (p.includes('github')) return <Github size={size} />;
    if (p.includes('linkedin')) return <Linkedin size={size} />;
    if (p.includes('instagram')) return <Instagram size={size} />;
    if (p.includes('youtube')) return <Youtube size={size} />;
    if (p.includes('facebook')) return <Facebook size={size} />;
    if (p.includes('whatsapp')) return <MessageCircle size={size} />;
    return <Globe size={size} />; // Ícone padrão
  };

  const theme = {
    bg: isDarkMode ? 'bg-[#023047]' : 'bg-[#D4F0FC]',
    text: isDarkMode ? 'text-white' : 'text-[#023047]',
    textMuted: isDarkMode ? 'text-gray-300' : 'text-gray-600',
    cardBg: isDarkMode ? 'bg-[#034061]' : 'bg-white',
    border: isDarkMode ? 'border-white/10' : 'border-[#023047]/10',
    navBg: isDarkMode
      ? scrolled
        ? 'bg-[#023047]/95 backdrop-blur-md shadow-lg'
        : 'bg-transparent'
      : scrolled
        ? 'bg-[#D4F0FC]/95 backdrop-blur-md shadow-lg'
        : 'bg-transparent',
  };

  // Componente de Logo Reutilizável
  const BrandLogo = () => (
    <div className="flex flex-col leading-none cursor-pointer group">
      {isLoadingLogo ? (
        <div className="h-12 w-48 animate-pulse bg-gray-500/20 rounded" />
      ) : siteSettings?.site_logo ? (
        <div className="relative h-12 w-48">
          <Image
            src={`${IMG_BASE_URL}${siteSettings.site_logo}`}
            alt={siteSettings.site_title || 'Logo'}
            fill
            className="object-contain object-left"
            priority
          />
        </div>
      ) : (
        <>
          <span className="text-2xl font-bold tracking-widest uppercase font-rajdhani group-hover:text-[#2ECC40] transition-colors">
            {siteSettings?.site_title?.split(' ')[0] || 'André'}
          </span>
          <div className="flex items-center gap-2">
            <div className="h-0.5 w-6 bg-[#2ECC40]" />
            <span className="text-xl font-bold tracking-widest text-[#89D6FB] font-rajdhani uppercase">
              {siteSettings?.site_title?.split(' ').slice(1).join(' ') || 'Ventura'}
            </span>
          </div>
        </>
      )}
    </div>
  );

  return (
    <div className={`min-h-screen transition-all duration-500 ${theme.bg} ${theme.text} font-sans selection:bg-[#2ECC40] selection:text-white`}>
      
      {/* HEADER */}
      <header className={`fixed top-0 w-full z-50 transition-all duration-300 border-b ${scrolled ? theme.border : 'border-transparent'} ${theme.navBg}`}>
        <div className="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
          <BrandLogo />

          {/* NAV DESKTOP */}
          <nav className="hidden md:flex items-center gap-8">
            <a href="#services" className="text-sm font-medium hover:text-[#2ECC40] transition-colors uppercase tracking-wider font-roboto">Especialidades</a>
            <a href="#projects" className="text-sm font-medium hover:text-[#2ECC40] transition-colors uppercase tracking-wider font-roboto">Portfólio</a>
            <button onClick={toggleTheme} className={`p-2 rounded-full transition-colors ${isDarkMode ? 'bg-white/10 hover:bg-white/20 text-[#FF8D37]' : 'bg-[#023047]/10 hover:bg-[#023047]/20 text-[#5D3FD3]'}`}>
              {isDarkMode ? <Sun size={20} /> : <Moon size={20} />}
            </button>
            <a href="#contact" className="bg-[#2ECC40] hover:bg-white hover:text-[#023047] text-[#023047] font-bold py-2.5 px-6 rounded shadow-[0_0_15px_rgba(46,204,64,0.4)] transition-all uppercase text-sm tracking-wide font-rajdhani">
              Fale Conosco
            </a>
          </nav>

          {/* MOBILE TOGGLE */}
          <div className="flex items-center gap-4 md:hidden">
            <button onClick={toggleTheme} className={isDarkMode ? 'text-[#FF8D37]' : 'text-[#5D3FD3]'}>
              {isDarkMode ? <Sun size={20} /> : <Moon size={20} />}
            </button>
            <button onClick={() => setIsMenuOpen(!isMenuOpen)} className="text-[#2ECC40]">
              {isMenuOpen ? <X size={28} /> : <Menu size={28} />}
            </button>
          </div>
        </div>

        {/* MENU MOBILE */}
        {isMenuOpen && (
          <div className={`md:hidden absolute top-20 left-0 w-full ${theme.cardBg} border-b ${theme.border} p-6 shadow-2xl flex flex-col gap-4`}>
            <a href="#services" onClick={() => setIsMenuOpen(false)} className="py-2 border-b border-white/5">Especialidades</a>
            <a href="#projects" onClick={() => setIsMenuOpen(false)} className="py-2 border-b border-white/5">Portfólio</a>
            <a href="#contact" onClick={() => setIsMenuOpen(false)} className="bg-[#2ECC40] text-[#023047] font-bold py-3 text-center rounded mt-2">Fale Conosco</a>
          </div>
        )}
      </header>

      {/* HERO SECTION */}
      <section className="relative pt-32 pb-20 lg:pt-48 lg:pb-32 px-6 overflow-hidden min-h-[600px] flex items-center">
        <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-[#5D3FD3]/20 rounded-full blur-[100px] -z-10 translate-x-1/2 -translate-y-1/2" />
        <div className="absolute bottom-0 left-0 w-[300px] h-[300px] bg-[#2ECC40]/10 rounded-full blur-[80px] -z-10 -translate-x-1/2 translate-y-1/2" />

        <div className="max-w-7xl mx-auto w-full grid lg:grid-cols-2 gap-12 items-center z-10">
          <div className="space-y-6">
            <div className={`inline-flex items-center gap-2 px-3 py-1 rounded-full border ${isDarkMode ? 'border-[#2ECC40]/30 bg-[#2ECC40]/10 text-[#2ECC40]' : 'border-[#5D3FD3]/30 bg-[#5D3FD3]/10 text-[#5D3FD3]'} text-xs font-bold uppercase tracking-wider`}>
              <span className="relative flex h-2 w-2">
                <span className={`animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 ${isDarkMode ? 'bg-[#2ECC40]' : 'bg-[#5D3FD3]'}`} />
                <span className={`relative inline-flex rounded-full h-2 w-2 ${isDarkMode ? 'bg-[#2ECC40]' : 'bg-[#5D3FD3]'}`} />
              </span>
              Disponível para novos projetos
            </div>

            <h1 className="text-4xl sm:text-6xl font-bold leading-[1.1] font-rajdhani">
              Transformando suas ideias em <br />
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-[#2ECC40] to-[#89D6FB]">experiências digitais.</span>
            </h1>

            <p className={`text-lg ${theme.textMuted} max-w-xl font-light leading-relaxed font-montserrat`}>
              Especialista em criação de sites e e-commerces. Utilizo a potência do <strong>WordPress</strong> e a flexibilidade do <strong>Elementor</strong> para entregar resultados profissionais que vendem.
            </p>

            <div className="flex flex-col sm:flex-row gap-4 pt-4">
              <a href="#projects" className="bg-[#2ECC40] hover:bg-[#25a535] text-[#023047] font-bold py-4 px-8 rounded flex items-center justify-center gap-2 transition-transform hover:-translate-y-1 shadow-[0_10px_20px_-10px_rgba(46,204,64,0.5)] uppercase font-rajdhani">
                Ver Projetos <ArrowRight size={20} />
              </a>
              <a href="#contact" className={`border ${isDarkMode ? 'border-white/20 hover:bg-white/5' : 'border-[#023047]/20 hover:bg-[#023047]/5'} font-medium py-4 px-8 rounded flex items-center justify-center transition-colors uppercase font-rajdhani`}>
                Solicitar Orçamento
              </a>
            </div>

            <div className="pt-8 border-t border-white/10 mt-8">
              <p className="text-xs uppercase tracking-widest mb-4 opacity-60">Ferramentas que domino</p>
              <div className="flex flex-wrap gap-6 opacity-80">
                <span className="font-bold text-lg flex items-center gap-2"><Layout size={20} className="text-[#2ECC40]" /> WordPress</span>
                <span className="font-bold text-lg flex items-center gap-2"><Layers size={20} className="text-[#5D3FD3]" /> Elementor & Divi</span>
                <span className="font-bold text-lg flex items-center gap-2"><ShoppingCart size={20} className="text-[#FF8D37]" /> WooCommerce</span>
              </div>
            </div>
          </div>

          <div className="relative hidden lg:block h-[500px]">
            <div className={`relative z-10 rounded-2xl overflow-hidden border ${theme.border} shadow-2xl group h-full`}>
              <div className="absolute inset-0 bg-[#023047]/40 group-hover:bg-transparent transition-all duration-500 z-10" />
              <Image src="/images/landing.jpg" alt="André Ventura - Web Developer" fill className="object-cover" priority />
            </div>
            <div className="absolute -bottom-6 -right-6 w-full h-full border-2 border-[#2ECC40] rounded-2xl -z-10" />
          </div>
        </div>
      </section>

      {/* SERVIÇOS */}
      <section id="services" className={`py-24 ${isDarkMode ? 'bg-[#034061]/30' : 'bg-white'}`}>
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center mb-16">
            <h2 className="text-3xl sm:text-5xl font-bold font-rajdhani uppercase mb-4">O que eu faço</h2>
            <div className="h-1 w-24 bg-[#2ECC40] mx-auto rounded-full" />
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            <div className={`p-8 rounded-2xl border ${theme.border} ${theme.cardBg} hover:border-[#2ECC40] transition-all hover:-translate-y-2 group`}>
              <div className="w-14 h-14 bg-[#2ECC40]/20 rounded-lg flex items-center justify-center text-[#2ECC40] mb-6"><Layout size={32} /></div>
              <h3 className="text-xl font-bold mb-3 font-rajdhani uppercase">Sites Institucionais</h3>
              <p className={`text-sm ${theme.textMuted} leading-relaxed`}>Criação de sites profissionais com <strong>WordPress</strong>. Entrega rápida e layouts modernos.</p>
            </div>
            <div className={`p-8 rounded-2xl border ${theme.border} ${theme.cardBg} hover:border-[#5D3FD3] transition-all hover:-translate-y-2 group`}>
              <div className="w-14 h-14 bg-[#5D3FD3]/20 rounded-lg flex items-center justify-center text-[#5D3FD3] mb-6"><ShoppingCart size={32} /></div>
              <h3 className="text-xl font-bold mb-3 font-rajdhani uppercase">Lojas WooCommerce</h3>
              <p className={`text-sm ${theme.textMuted} leading-relaxed`}>Implementação completa de <strong>WooCommerce</strong> com integração de pagamentos e correios.</p>
            </div>
            <div className={`p-8 rounded-2xl border ${theme.border} ${theme.cardBg} hover:border-[#FF8D37] transition-all hover:-translate-y-2 group`}>
              <div className="w-14 h-14 bg-[#FF8D37]/20 rounded-lg flex items-center justify-center text-[#FF8D37] mb-6"><PaletteIcon size={32} /></div>
              <h3 className="text-xl font-bold mb-3 font-rajdhani uppercase">Elementor & Divi</h3>
              <p className={`text-sm ${theme.textMuted} leading-relaxed`}>Domínio total dos principais construtores visuais do mercado.</p>
            </div>
          </div>
        </div>
      </section>

      {/* PROJETOS */}
      <section id="projects" className="py-24 relative overflow-hidden">
        <div className="max-w-7xl mx-auto px-6">
          <div className="flex items-center gap-4 mb-16">
            <h2 className="text-3xl font-bold uppercase whitespace-nowrap font-rajdhani">Meus Projetos</h2>
            <div className="h-px w-full bg-gradient-to-r from-[#2ECC40] to-transparent opacity-50 mt-1" />
          </div>
          <ProjectsArea />
        </div>
      </section>

      {/* CONTATO */}
      <section id="contact" className={`py-20 border-t ${theme.border}`}>
        <div className="max-w-4xl mx-auto px-6">
          <div className={`rounded-3xl p-8 md:p-12 ${isDarkMode ? 'bg-gradient-to-br from-[#034061] to-[#023047]' : 'bg-white shadow-xl'} border ${theme.border}`}>
            <div className="text-center mb-10">
              <h2 className="text-3xl font-bold font-rajdhani uppercase mb-4">Vamos conversar?</h2>
              <p className={theme.textMuted}>Preencha o formulário abaixo para solicitar um orçamento.</p>
            </div>
            <DynamicForm slug="contact-form-main" />
          </div>
        </div>
      </section>

      {/* FOOTER PROFISSIONAL */}
      <footer className={`pt-16 pb-8 border-t ${theme.border} ${isDarkMode ? 'bg-[#011a28]' : 'bg-[#D4F0FC]'}`}>
        <div className="max-w-7xl mx-auto px-6">
          
          <div className="grid md:grid-cols-4 gap-12 mb-12">
            {/* Coluna 1: Identidade */}
            <div className="md:col-span-2 space-y-4">
              <BrandLogo />
              <p className={`max-w-sm text-sm leading-relaxed ${theme.textMuted}`}>
                {siteSettings?.site_description || 'Desenvolvimento Web especializado em criar soluções digitais que impulsionam negócios através de tecnologia e design estratégico.'}
              </p>
            </div>

            {/* Coluna 2: Contato Rápido */}
            <div className="space-y-4">
              <h4 className="text-lg font-bold font-rajdhani uppercase text-[#2ECC40]">Contato</h4>
              <ul className="space-y-3">
                {siteSettings?.contact_email && (
                  <li>
                    <a href={`mailto:${siteSettings.contact_email}`} className={`flex items-center gap-3 text-sm ${theme.textMuted} hover:text-[#2ECC40] transition-colors`}>
                      <Mail size={18} /> {siteSettings.contact_email}
                    </a>
                  </li>
                )}
                {siteSettings?.contact_phone && (
                   <li>
                   <a href={`https://wa.me/${siteSettings.contact_phone.replace(/\D/g,'')}`} target="_blank" className={`flex items-center gap-3 text-sm ${theme.textMuted} hover:text-[#2ECC40] transition-colors`}>
                     <Phone size={18} /> {siteSettings.contact_phone}
                   </a>
                 </li>
                )}
                {siteSettings?.contact_address && (
                  <li className={`flex items-start gap-3 text-sm ${theme.textMuted}`}>
                    <MapPin size={18} className="shrink-0 mt-0.5" /> 
                    <span>{siteSettings.contact_address}</span>
                  </li>
                )}
              </ul>
            </div>

            {/* Coluna 3: Redes Sociais */}
            <div className="space-y-4">
              <h4 className="text-lg font-bold font-rajdhani uppercase text-[#89D6FB]">Conecte-se</h4>
              <div className="flex flex-wrap gap-3">
                {siteSettings?.social_links && siteSettings.social_links.length > 0 ? (
                  siteSettings.social_links.map((link, idx) => (
                    <a 
                      key={idx}
                      href={link.url}
                      target="_blank"
                      rel="noreferrer"
                      className={`p-2 rounded-lg border ${theme.border} ${isDarkMode ? 'bg-white/5 hover:bg-white/10' : 'bg-[#023047]/5 hover:bg-[#023047]/10'} hover:text-[#2ECC40] transition-all`}
                      title={link.platform}
                    >
                      {getSocialIcon(link.platform)}
                    </a>
                  ))
                ) : (
                  <span className={`text-xs ${theme.textMuted}`}>Sem redes cadastradas.</span>
                )}
              </div>
            </div>
          </div>

          {/* Barra Inferior */}
          <div className={`pt-8 border-t ${theme.border} flex flex-col md:flex-row justify-between items-center gap-4 text-xs font-medium opacity-80`}>
            <div className={theme.textMuted}>
              © {new Date().getFullYear()} <strong className="uppercase">{siteSettings?.site_title || 'ANDRÉ VENTURA'}</strong>. Todos os direitos reservados.
            </div>
            <div className="flex items-center gap-1">
              <span className={theme.textMuted}>Desenvolvido por</span>
              <a 
                href="https://asventura.me/" 
                target="_blank" 
                rel="noreferrer"
                className="text-[#2ECC40] hover:underline font-bold"
              >
                André Ventura
              </a>
            </div>
          </div>

        </div>
      </footer>
    </div>
  );
}