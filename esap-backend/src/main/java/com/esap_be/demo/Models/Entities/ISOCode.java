package com.esap_be.demo.Models.Entities;

import lombok.AllArgsConstructor;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.springframework.data.annotation.Id;
import org.springframework.data.redis.core.RedisHash;

import java.util.List;

@RedisHash("ISOCodeDetails")
@NoArgsConstructor
@AllArgsConstructor
@Getter
@Setter
public class ISOCode {

    @Id
    private String code;

    private List<ISOCodeDetails> details;
}